<?php

namespace WebImage\Models\Commands;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use WebImage\Application\ApplicationInterface;
use WebImage\Commands\Command;
use WebImage\Console\FlagOption;
use WebImage\Console\InputInterface;
use WebImage\Console\OutputInterface;
use WebImage\Console\ValueOption;
use WebImage\Models\Actions\ConsoleProgressHandler;
use WebImage\Models\Actions\GenerateClassesAction;
use WebImage\Models\Providers\FileModelDefinitionProvider;
use WebImage\Models\Providers\ModelDefinitionChangeDetector;
use WebImage\Models\Providers\ModelDefinitionProviderInterface;
use WebImage\Models\Providers\ModelDefinitionWatcher;
use WebImage\Models\Services\RepositoryInterface;


class GenerateModelClassesCommand extends Command
{
    private ?string $baseNamespace;
    private ?string $outputDir;
    private ?string $templateDir;

    public function __construct(?string $name = null, ?string $baseNamespace = null, ?string $outputDir = null, ?string $templateDir = null)
    {
        $this->baseNamespace = $baseNamespace;
        $this->outputDir     = $outputDir;
        $this->templateDir   = $templateDir;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Generate entity and repository classes from YAML model definitions');
        $this->addOption(ValueOption::required('output-dir', 'Output directory for generated classes', 'o'));
        $this->addOption(ValueOption::optional('template-dir', 'Directory containing template files', 't'));
        $this->addOption(FlagOption::create('force', 'Force regeneration of all files (including non-base classes)', 'f'));
        $this->addOption(FlagOption::create('watch', 'Watch for changes and regenerate automatically', 'w'));
        $this->addOption(ValueOption::optional('watch-interval', 'Watch interval in seconds', 'i', '1'));
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $watch = $input->getOption('watch');
        if ($watch) {
            return $this->executeWatch($input, $output);
        }
        return $this->executeOnce($input, $output);
    }

    private function executeOnce(InputInterface $input, OutputInterface $output): int
    {
        $force     = $input->getOption('force');
        /** @var RepositoryInterface $repo */
        $repo = $this->getContainer()->get(RepositoryInterface::class);

        /** @var ModelDefinitionProviderInterface $provider */
        $provider = $this->getContainer()->get(ModelDefinitionProviderInterface::class);

        // Check for changes
        $detector = new ModelDefinitionChangeDetector();
        $changes = $detector->detectChanges($provider, $repo->getDictionaryService());

        if (!$force && !$changes->hasChanges()) {
            $output->writeln("<info>No changes detected. Use --force to regenerate all files.</info>");

            return 0;
        }

        if ($changes->hasChanges()) {
            $this->reportChanges($changes, $output);
        }

        // Create and execute the action
        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);

        $action = new GenerateClassesAction(
            $repo,
            $app,
            $this->baseNamespace,
            $this->templateDir
        );

        $options = [
            'output-dir' => $this->getOutputDir($input),
            'template-dir' => $this->getTemplateDir($input),
            'force' => $force
        ];

        // Create progress handler for console output
        $progress = new ConsoleProgressHandler($output);

        // Execute action with progress reporting
        $result = $action->execute($changes, $options, $progress);

        return $result->isSuccess() ? 0 : 1;
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

                // Regenerate
                $this->executeOnce($input, $output);
            },
            $interval,
            function() use ($input, $output) {
                // Initial generation
                $output->writeln("<info>Performing initial generation...</info>");
                $this->executeOnce($input, $output);
                $output->writeln("\n<info>Watching for changes...</info>");
            }
        );

        return 0;
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

    /**
     * Allow input arguments to override the default output directory.
     * @param InputInterface $input
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getOutputDir(InputInterface $input): string
    {
        $outputDir = $input->getOption('output-dir');
        if (!empty($outputDir)) return rtrim($outputDir, '/\\');

        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);

        return $this->outputDir ?? $app->getProjectPath() . '/src/Models';
    }
}
