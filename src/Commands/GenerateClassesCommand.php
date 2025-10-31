<?php

namespace WebImage\Models\Commands;

use WebImage\Commands\Command;
use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;
use WebImage\Console\FlagOption;
use WebImage\Console\ValueOption;
use WebImage\Models\Actions\ConsoleProgressHandler;
use WebImage\Models\Actions\GenerateClassesAction;
use WebImage\Models\Providers\ModelDefinitionChangeSet;
use WebImage\Models\Services\RepositoryInterface;

/**
 * Generate entity and repository classes from compiled model definitions
 * This command requires that models have been compiled first via SyncModelsCommand
 */
class GenerateClassesCommand extends Command
{
    private string $compiledModelsPath;
    private ?string $defaultBaseNamespace;
    private ?string $defaultOutputDir;
    private ?string $defaultTemplateDir;

    public function __construct(
        ?string $name = null,
        ?string $compiledModelsPath = null,
        ?string $defaultBaseNamespace = null,
        ?string $defaultOutputDir = null,
        ?string $defaultTemplateDir = null
    ) {
        $this->compiledModelsPath = $compiledModelsPath ?? 'generated/compiled-models.php';
        $this->defaultBaseNamespace = $defaultBaseNamespace ?? 'App\\Models';
        $this->defaultOutputDir = $defaultOutputDir;
        $this->defaultTemplateDir = $defaultTemplateDir;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('models:classes')
            ->setDescription('Generate entity and repository classes from compiled model definitions')
            ->addOption(ValueOption::required('output-dir', 'Output directory for generated classes', 'o'))
            ->addOption(ValueOption::optional('template-dir', 'Template directory', 't'))
            ->addOption(ValueOption::optional('base-namespace', 'Base namespace for generated classes', 'n'))
            ->addOption(FlagOption::create('force', 'Force regeneration of all files', 'f'));
    }

    public function execute(ConsoleInput $input, ConsoleOutput $output): int
    {
        // Ensure compiled models exist
        if (!file_exists($this->compiledModelsPath)) {
            $output->error('Compiled models not found at: ' . $this->compiledModelsPath);
            $output->info('Run models:sync first to compile your model definitions.');
            return 1;
        }

        // Get repository (automatically loads from compiled file via ModelDefinitionServiceProvider)
        $repository = $this->getContainer()->get(RepositoryInterface::class);

        // Create progress handler for console output
        $progress = new ConsoleProgressHandler($output);

        // Build options from input
        $outputDir = $input->getOption('output-dir') ?? $this->defaultOutputDir;
        if (empty($outputDir)) {
            $output->error('Output directory is required. Use --output-dir option.');
            return 1;
        }

        $options = [
            'output-dir' => rtrim($outputDir, '/\\'),
            'template-dir' => $input->getOption('template-dir') ?? $this->defaultTemplateDir,
            'base-namespace' => $input->getOption('base-namespace') ?? $this->defaultBaseNamespace,
            'force' => $input->getOption('force', false)
        ];

        // Create action
        $action = new GenerateClassesAction(
            $repository,
            $options['output-dir'],
            $options['base-namespace'],
            $options['template-dir']
        );

        // Create full changeset (mark all models as modified)
        $changeSet = $this->createFullChangeSet($repository);

        // Execute action
        $output->writeln('<info>Generating entity and repository classes...</info>');
        $output->writeln(str_repeat('-', 60));

        $result = $action->execute($changeSet, $options, $progress);

        // Display result
        $output->writeln('');
        if ($result->isSuccess()) {
            $generatedCount = $result->getData('generated_count');
            $skippedCount = $result->getData('skipped_count');
            $output->success('Class generation completed!');
            $output->info("Generated: {$generatedCount}, Skipped: {$skippedCount}");
            return 0;
        } else {
            $output->error('Class generation failed.');
            foreach ($result->getMessagesByLevel('error') as $msg) {
                $output->error('  ' . $msg['message']);
            }
            return 1;
        }
    }

    /**
     * Create a full changeset marking all models as modified
     */
    private function createFullChangeSet(RepositoryInterface $repository): ModelDefinitionChangeSet
    {
        $changeSet = new ModelDefinitionChangeSet();
        $definitions = $repository->getDictionaryService()->getModelDefinitions();

        foreach ($definitions as $definition) {
            $changeSet->addModified($definition->getName());
        }

        return $changeSet;
    }
}