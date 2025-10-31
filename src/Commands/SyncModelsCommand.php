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
use WebImage\Models\Providers\ModelDefinitionChangeSet;
use WebImage\Models\Providers\ModelDefinitionCompiler;
use WebImage\Models\Providers\ModelDefinitionWatcher;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Config\Config;

/**
 * Orchestrator command that syncs all model-related artifacts when definitions change
 * This command compiles YAML → PHP, then runs import and class generation actions
 */
class SyncModelsCommand extends Command
{
    private ?string $baseNamespace;
    private ?string $outputDir;
    private ?string $templateDir;
    private string $compiledModelsPath;

    /** @var ModelActionInterface[] */
    private array $actions = [];

    public function __construct(
        ?string $name = null,
        ?string $baseNamespace = null,
        ?string $baseClassDirectory = null,
        ?string $templateDir = null,
        ?string $compiledModelsPath = null
    ) {
        $this->baseNamespace = $baseNamespace ?? 'App\\Models';
        $this->outputDir = $baseClassDirectory;
        $this->templateDir = $templateDir;
        $this->compiledModelsPath = $compiledModelsPath ?? 'generated/compiled-models.php';

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Synchronize all model artifacts (compile + import + class generation)');
        $this->addOption(ValueOption::required('output-dir', 'Output directory for generated classes', 'o'));
        $this->addOption(ValueOption::optional('template-dir', 'Directory containing template files', 't'));
        $this->addOption(ValueOption::optional('base-namespace', 'Base namespace for generated classes', 'n'));
        $this->addOption(FlagOption::create('force', 'Force regeneration of all files', 'f'));
        $this->addOption(FlagOption::create('watch', 'Watch for changes and sync automatically', 'w'));
        $this->addOption(ValueOption::optional('watch-interval', 'Watch interval in seconds', 'i', '1'));
        $this->addOption(FlagOption::create('skip-import', 'Skip database import'));
        $this->addOption(FlagOption::create('skip-classes', 'Skip class generation'));
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $watch = $input->getOption('watch');

        if ($watch) {
            return $this->executeWithWatch($input, $output);
        }

        return $this->executeSingle($input, $output);
    }

    private function executeSingle(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>Starting model sync...</info>");
        $output->writeln("");

        // Step 1: Compile YAML files to PHP
        if (!$this->compileModels($output)) {
            return 1;
        }
        // Create full changeset (mark all models as modified)
        $changeSet = $this->createFullChangeSet();
        // Step 2: Run all actions (import, generate classes)
        return $this->runAllActions($input, $output, $changeSet);
    }

    private function executeWithWatch(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->getApplicationConfig()->get('webimage/models', new Config());
        $modelFiles = $config->get('models');
        $variables = $config->get('variables');

        if (!is_array($modelFiles)) {
            $output->writeln("<e>Config at webimage/models.models must be an array of file paths</e>");
            return 1;
        }

        $fileProvider = new FileModelDefinitionProvider($modelFiles, $variables);
        $interval = (int) $input->getOption('watch-interval');

        $output->writeln("<info>Watching for changes (interval: {$interval}s)...</info>");
        $output->writeln("Press Ctrl+C to stop watching");
        $output->writeln("");

        $watcher = new ModelDefinitionWatcher($fileProvider);

        $watcher->watch(
            function(ModelDefinitionChangeSet $changes, array $changedFiles) use ($input, $output, $fileProvider) {
                $output->writeln("\n<info>Changes detected in files:</info>");
                foreach ($changedFiles as $file) {
                    $output->writeln("  - " . basename($file));
                }
                $output->writeln("");

                $this->reportChanges($changes, $output);
                $output->writeln("");

                // Recompile and run actions
                $this->compileModels($output);
                $this->reloadChangedModels($fileProvider, $changes, $output);
                $this->runAllActions($input, $output, $changes);
            },
            $interval,
            function() use ($input, $output) {
                // Initial sync
                $output->writeln("<info>Performing initial sync...</info>");
                $output->writeln("");
                $this->executeSingle($input, $output);
                $output->writeln("\n<info>Watching for changes...</info>");
            }
        );

        return 0;
    }

    /**
     * Compile YAML model files to PHP
     */
    private function compileModels(OutputInterface $output): bool
    {
        try {
            $output->writeln("<info>Compiling model definitions...</info>");

            $config = $this->getApplicationConfig()->get('webimage/models', new Config());
            $modelFiles = $config->get('models');
            $variables = $config->get('variables');

            if (!is_array($modelFiles)) {
                $output->writeln("<e>Config at webimage/models.models must be an array of file paths</e>");
                return false;
            }

            // Create file provider to load YAML files
            $fileProvider = new FileModelDefinitionProvider($modelFiles, $variables);

            // Get all definitions and source metadata
            $definitions = $fileProvider->getAllModelDefinitions();
            $sourceFiles = $fileProvider->getSourceFileMetadata();

            // Compile to PHP
            $compiler = new ModelDefinitionCompiler();
            $compiler->compile($definitions, $sourceFiles, $this->compiledModelsPath);

            $output->writeln("  Compiled " . count($definitions) . " model(s) to: {$this->compiledModelsPath}");
            $output->writeln("");

            return true;
        } catch (\Exception $e) {
            $output->writeln("<e>Compilation failed: " . $e->getMessage() . "</e>");
            return false;
        }
    }

    /**
     * Run all registered actions (import, generate classes)
     */
    private function runAllActions(InputInterface $input, OutputInterface $output, ModelDefinitionChangeSet $changeSet): int
    {
        $skipImport = $input->getOption('skip-import');
        $skipClasses = $input->getOption('skip-classes');

        $repository = $this->getRepository();

        // Create progress handler
        $progress = new ConsoleProgressHandler($output);

        // Build options from input
        $options = [
            'output-dir' => $this->getOutputDir($input),
            'template-dir' => $this->getTemplateDir($input),
            'base-namespace' => $input->getOption('base-namespace') ?? $this->baseNamespace,
            'force' => $input->getOption('force', false)
        ];

        // Initialize actions
        $this->initializeActions($repository, $options);

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

            // Check if action should run
            if (!$action->shouldRun($changeSet)) {
                $output->writeln("<comment>Skipping (no relevant changes): {$action->getDescription()}</comment>");
                continue;
            }

            $output->writeln("<info>Running: {$action->getDescription()}</info>");
            $output->writeln(str_repeat('-', 60));

            // Execute action with progress reporting
            $result = $action->execute($changeSet, $options, $progress);

            if ($result->isFailure()) {
                $allSuccess = false;
                $output->writeln("<e>Action failed: {$action->getDescription()}</e>");
            }

            $actionsRun++;
            $output->writeln("");
        }

        if ($actionsRun === 0) {
            $output->writeln("<comment>No actions were run.</comment>");
        } else {
            $output->writeln(str_repeat('=', 60));
            if ($allSuccess) {
                $output->writeln("<info>✓ All {$actionsRun} action(s) completed successfully!</info>");
            } else {
                $output->writeln("<e>✗ Some actions failed. Check output above.</e>");
            }
        }

        return $allSuccess ? 0 : 1;
    }

    /**
     * Initialize actions that should run during sync
     */
    private function initializeActions(RepositoryInterface $repository, array $options): void
    {
        if (!empty($this->actions)) {
            return; // Already initialized
        }

        // Add import action
        $this->actions[] = new ImportModelsAction($repository);

        // Add class generation action
        $this->actions[] = new GenerateClassesAction(
            $repository,
            $options['output-dir'],
            $options['base-namespace'],
            $options['template-dir']
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

    /**
     * Create a full changeset marking all models as modified
     */
    private function createFullChangeSet(): ModelDefinitionChangeSet
    {
        $repository = $this->getRepository();
        $changeSet = new ModelDefinitionChangeSet();
        $definitions = $repository->getDictionaryService()->getModelDefinitions();

        foreach ($definitions as $definition) {
            $changeSet->addModified($definition->getName());
        }

        return $changeSet;
    }

    private function reportChanges(ModelDefinitionChangeSet $changes, OutputInterface $output): void
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

    private function reloadChangedModels(FileModelDefinitionProvider $fileProvider, ModelDefinitionChangeSet $changes, OutputInterface $output)
    {
        $repository = $this->getRepository();

        $dictionary = $repository->getDictionaryService();

        foreach($changes->getRemoved() as $removedModel) {
            $dictionary->removeModelDefinition($removedModel);
        }

        // Add or re-add model definition
        $modelNames = array_merge($changes->getAdded(), $changes->getModified());
        foreach($modelNames as $modelName) {
            $dictionary->removeModelDefinition($modelName);
            $dictionary->addModelDefinition($fileProvider->getModelDefinition($modelName)); // New definition
        }
    }

    private function getRepository(): RepositoryInterface
    {
        return $this->container->get(RepositoryInterface::class);
    }

    private function getTemplateDir(InputInterface $input): ?string
    {
        $templateDir = $input->getOption('template-dir');
        if (!empty($templateDir)) {
            return $templateDir;
        }

        if ($this->templateDir !== null) {
            return $this->templateDir;
        }

        return null;
    }

    private function getOutputDir(InputInterface $input): string
    {
        $outputDir = $input->getOption('output-dir');
        if (!empty($outputDir)) {
            return rtrim($outputDir, '/\\');
        }

        if ($this->outputDir !== null) {
            return $this->outputDir;
        }

        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);
        return $app->getProjectPath() . '/src/Models';
    }

    private function getApplicationConfig(): Config
    {
        /** @var ApplicationInterface $app */
        $app = $this->getContainer()->get(ApplicationInterface::class);
        return $app->getConfig();
    }
}
