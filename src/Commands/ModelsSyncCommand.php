<?php

namespace WebImage\Models\Commands;

use WebImage\Application\ApplicationInterface;
use WebImage\Commands\Command;
use WebImage\Console\FlagOption;
use WebImage\Console\InputInterface;
use WebImage\Console\OutputInterface;
use WebImage\Console\ValueOption;
use WebImage\Models\Actions\ConsoleProgressHandler;
use WebImage\Models\Actions\GenerateClassesAction;
use WebImage\Models\Actions\ImportModelsAction;
use WebImage\Models\Actions\ModelActionInterface;
use WebImage\Models\Providers\FileModelDefinitionProvider;
use WebImage\Models\Providers\ModelDefinitionChangeDetector;
use WebImage\Models\Providers\ModelDefinitionProviderInterface;
use WebImage\Models\Providers\ModelDefinitionWatcher;
use WebImage\Models\Services\RepositoryInterface;

/**
 * Orchestrator command that syncs all model-related artifacts when definitions change
 * This command runs import, class generation, and any other registered actions
 */
class ModelsSyncCommand extends Command
{
    private ?string $baseNamespace;
    private ?string $outputDir;
    private ?string $templateDir;
    /** @var ModelActionInterface[] */
    private array $actions = [];

    public function __construct(
        ?string $name = null,
        ?string $baseNamespace = null,
        ?string $outputDir = null,
        ?string $templateDir = null
    ) {
        $this->baseNamespace = $baseNamespace;
        $this->outputDir = $outputDir;
        $this->templateDir = $templateDir;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Synchronize all model artifacts (import + class generation) when definitions change');
        $this->addOption(ValueOption::required('output-dir', 'Output directory for generated classes', 'o'));
        $this->addOption(ValueOption::optional('template-dir', 'Directory containing template files', 't'));
        $this->addOption(FlagOption::create('force', 'Force sync even if no changes detected', 'f'));
        $this->addOption(FlagOption::create('watch', 'Watch for changes and sync automatically', 'w'));
        $this->addOption(ValueOption::optional('watch-interval', 'Watch interval in seconds', 'i', '1'));
        $this->addOption(FlagOption::create('skip-import', 'Skip database import'));
        $this->addOption(FlagOption::create('skip-classes', 'Skip class generation'));
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Initialize actions
        $this->initializeActions();

        $watch = $input->getOption('watch');

        if ($watch) {
            return $this->executeWatch($input, $output);
        }

        return $this->executeOnce($input, $output);
    }

    private function executeOnce(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        /** @var RepositoryInterface $repo */
        $repo = $this->getContainer()->get(RepositoryInterface::class);

        /** @var ModelDefinitionProviderInterface $provider */
        $provider = $this->getContainer()->get(ModelDefinitionProviderInterface::class);

        // Check for changes
        $detector = new ModelDefinitionChangeDetector();
        $changes = $detector->detectChanges($provider, $repo->getDictionaryService());

        if (!$force && !$changes->hasChanges()) {
            $output->writeln("<info>No changes detected. Use --force to sync anyway.</info>");
            return 0;
        }

        if ($changes->hasChanges()) {
            $this->reportChanges($changes, $output);
        } else {
            $output->writeln("<info>Forcing sync (no changes detected)...</info>");
        }

        // Run all actions
        return $this->runActions($changes, $input, $output);
    }

    private function executeWatch(InputInterface $input, OutputInterface $output): int
    {
        /** @var ModelDefinitionProviderInterface $provider */
        $provider = $this->getContainer()->get(ModelDefinitionProviderInterface::class);

        if (!($provider instanceof FileModelDefinitionProvider)) {
            $output->writeln("<e>Watch mode only supports file-based model definitions</e>");
            return 1;
        }

        $interval = (int) $input->getOption('watch-interval');
        $output->writeln("<info>Watching for changes (interval: {$interval}s)...</info>");
        $output->writeln("Press Ctrl+C to stop watching");

        $watcher = new ModelDefinitionWatcher($provider);

        $watcher->watch(
            function($changes, $changedFiles) use ($input, $output) {
                $output->writeln("\n<info>Changes detected in files:</info>");
                foreach ($changedFiles as $file) {
                    $output->writeln("  - " . basename($file));
                }

                $this->reportChanges($changes, $output);
                $output->writeln("");

                // Run sync
                $this->runActions($changes, $input, $output);
            },
            $interval,
            function() use ($input, $output) {
                // Initial sync
                $output->writeln("<info>Performing initial sync...</info>");
                $this->executeOnce($input, $output);
                $output->writeln("\n<info>Watching for changes...</info>");
            }
        );

        return 0;
    }

    private function initializeActions(): void
    {
        if (!empty($this->actions)) {
            return; // Already initialized
        }

        /** @var RepositoryInterface $repo */
        $repo = $this->getContainer()->get(RepositoryInterface::class);

        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);

        // Add import action
        $this->actions[] = new ImportModelsAction($repo);

        // Add class generation action
        $this->actions[] = new GenerateClassesAction(
            $repo,
            $app,
            $this->baseNamespace,
            $this->templateDir
        );

        // Future: Add more actions here
        // $this->actions[] = new GenerateApiRoutesAction(...);
    }

    /**
     * Allow external code to add custom actions
     */
    public function addAction(ModelActionInterface $action): void
    {
        $this->actions[] = $action;
    }

    private function runActions($changes, InputInterface $input, OutputInterface $output): int
    {
        $skipImport = $input->getOption('skip-import');
        $skipClasses = $input->getOption('skip-classes');

        $options = [
            'output-dir' => $this->getOutputDir($input),
            'template-dir' => $this->getTemplateDir($input),
            'force' => $input->getOption('force')
        ];

        // Create progress handler for console output
        $progress = new ConsoleProgressHandler($output);

        $allSuccess = true;
        $actionsRun = 0;

        foreach ($this->actions as $action) {
            // Skip actions based on flags
            if ($skipImport && $action instanceof ImportModelsAction) {
                $output->writeln("<comment>Skipping: {$action->getDescription()}</comment>");
                continue;
            }
            if ($skipClasses && $action instanceof GenerateClassesAction) {
                $output->writeln("<comment>Skipping: {$action->getDescription()}</comment>");
                continue;
            }

            // Check if action should run for these changes
            if (!$action->shouldRun($changes)) {
                $output->writeln("<comment>Skipping (no relevant changes): {$action->getDescription()}</comment>");
                continue;
            }

            $output->writeln("\n<info>Running: {$action->getDescription()}</info>");
            $output->writeln(str_repeat('-', 60));

            // Execute action with progress reporting
            $result = $action->execute($changes, $options, $progress);

            if ($result->isFailure()) {
                $allSuccess = false;
                $output->writeln("<e>Action failed: {$action->getDescription()}</e>");
            }

            $actionsRun++;
        }

        if ($actionsRun === 0) {
            $output->writeln("\n<comment>No actions were run.</comment>");
        } else {
            $output->writeln("\n" . str_repeat('=', 60));
            if ($allSuccess) {
                $output->writeln("<info>✓ All {$actionsRun} action(s) completed successfully!</info>");
            } else {
                $output->writeln("<e>✗ Some actions failed. Check output above.</e>");
            }
        }

        return $allSuccess ? 0 : 1;
    }

    private function reportChanges($changes, OutputInterface $output): void
    {
        if (!empty($changes->getAdded())) {
            $output->writeln("<info>Added models: " . implode(', ', $changes->getAdded()) . "</info>");
        }
        if (!empty($changes->getModified())) {
            $output->writeln("<info>Modified models: " . implode(', ', $changes->getModified()) . "</info>");
        }
        if (!empty($changes->getRemoved())) {
            $output->writeln("<info>Removed models: " . implode(', ', $changes->getRemoved()) . "</info>");
        }
    }

    private function getTemplateDir(InputInterface $input): string
    {
        $templateDir = $input->getOption('template-dir');
        if (!empty($templateDir)) {
            return $templateDir;
        }

        if ($this->templateDir !== null) {
            return $this->templateDir;
        }

        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);
        return $app->getProjectPath() . '/src/Models/Templates';
    }

    private function getOutputDir(InputInterface $input): string
    {
        $outputDir = $input->getOption('output-dir');
        if (!empty($outputDir)) {
            return rtrim($outputDir, '/\\');
        }

        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);

        return $this->outputDir ?? $app->getProjectPath() . '/src/Models';
    }
}
