<?php

namespace WebImage\Models\Commands;

use WebImage\Application\ApplicationInterface;
use WebImage\Commands\Command;
use WebImage\Console\FlagOption;
use WebImage\Console\InputInterface;
use WebImage\Console\OutputInterface;
use WebImage\Console\Table;
use WebImage\Console\TableSeparator;
use WebImage\Console\ValueOption;
use WebImage\Models\Actions\ConsoleProgressHandler;
use WebImage\Models\Actions\ImportModelsAction;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\PropertyPathDefinition;
use WebImage\Models\Entities\Model;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Providers\FileModelDefinitionProvider;
use WebImage\Models\Providers\ModelDefinitionChangeDetector;
use WebImage\Models\Providers\ModelDefinitionProviderInterface;
use WebImage\Models\Providers\ModelDefinitionWatcher;
use WebImage\Models\Services\ModelServiceInterface;
use WebImage\Models\Services\RepositoryInterface;

class ImportModelsCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Imports models from configured \'webimage/models\' config key');
        $this->addOption(ValueOption::optional('limit-model', 'Limit the model(s) to be imported. Specify multiple models comma delimited', 'm'));
        $this->addOption(FlagOption::create('debug', 'Dumps the structure of an import without importing any actual values', 'd'));
        $this->addOption(FlagOption::create('watch', 'Watch for changes and import automatically', 'w'));
        $this->addOption(ValueOption::optional('watch-interval', 'Watch interval in seconds', 'i', '1'));
    }

    public function execute(InputInterface $input, OutputInterface $output):int
    {
        $watch = $input->getOption('watch');
        if ($watch) {
            return $this->executeWatch($input, $output);
        }
        return $this->executeOnce($input, $output);
    }

    private function executeOnce(InputInterface $input, OutputInterface $output): int
    {
        /** @var RepositoryInterface $repo */
        $repo = $this->getContainer()->get(RepositoryInterface::class);

        /** @var ModelDefinitionProviderInterface $provider */
        $provider = $this->getContainer()->get(ModelDefinitionProviderInterface::class);

        // In debug mode, don't check for changes - just display structure
        if ($input->getOption('debug')) {
            return $this->executeDebug($input, $output);
        }

        // Check for changes
        $detector = new ModelDefinitionChangeDetector();
        $changes = $detector->detectChanges($provider, $repo->getDictionaryService());

        if (!$changes->hasChanges()) {
            $output->writeln("<info>No changes detected.</info>");
            return 0;
        }

        $this->reportChanges($changes, $output);

        // Create and execute the action
        $action = new ImportModelsAction($repo);

        $options = [
            'limit-model' => $input->getOption('limit-model')
        ];

        // Create progress handler for console output
        $progress = new ConsoleProgressHandler($output);

        // Execute action with progress reporting
        $result = $action->execute($changes, $options, $progress);

        return $result->isSuccess() ? 0 : 1;
    }

    private function executeDebug(InputInterface $input, OutputInterface $output): int
    {
        /** @var RepositoryInterface $repo */
        $repo = $this->getContainer()->get(RepositoryInterface::class);
        $modelDefs = $repo->getDictionaryService()->getModelDefinitions();

        // Filter by limit-model option if provided
        $limitModels = $input->getOption('limit-model');
        if ($limitModels) {
            $limitModelNames = array_map('trim', explode(',', $limitModels));
            $modelDefs = array_filter($modelDefs, function($modelDef) use ($limitModelNames) {
                return in_array($modelDef->getName(), $limitModelNames);
            });
        }

        $models = [];
        foreach ($modelDefs as $modelDef) {
            $model = $repo->getModelService()->getModel($modelDef->getName());
            if ($model === null) {
                $model = $repo->getModelService()->create(
                    $modelDef->getName(),
                    $modelDef->getPluralName(),
                    $modelDef->getFriendlyName(),
                    $modelDef->getFriendlyName()
                );
            }
            $model->setDef($modelDef);
            $models[] = $model;
        }

        $this->displayStructure($input, $output, $repo->getModelService(), $models);

        return 0;
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

                // Re-import
                $this->executeOnce($input, $output);
            },
            $interval,
            function() use ($input, $output) {
                // Initial import
                $output->writeln("<info>Performing initial import...</info>");
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

    /**
     * Display the structure of models being imported (debug mode)
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param ModelServiceInterface $modelService
     * @param Model[] $models
     */
    public function displayStructure(InputInterface $input, OutputInterface $output, ModelServiceInterface $modelService, array $models): void
    {
        $table = new Table($output);
        $table->setHeaders(['Property', 'Type', '# Values', 'Reference', 'Relationship', 'Comment']);
        $first = true;

        foreach ($models as $model) {
            $modelDef = $model->getDef();

            if (!$first) {
                $table->addRow(new TableSeparator());
            }

            $first = false;
            $table->addRow([$modelDef->getPluralName()]);
            $table->addRow(new TableSeparator());

            foreach ($modelDef->getProperties() as $propDef) {
                $table->addRow([
                    '  .' . $propDef->getName(),
                    $propDef->getDataType(),
                    $propDef->isMultiValued() ? 'Multi' : 'Single',
                    $this->getReferenceDescription($propDef),
                    $this->getRelationshipDescription($modelService, $propDef),
                    $propDef->getComment()
                ]);
            }
        }

        $table->render();
    }

    private function getReferenceDescription(PropertyDefinition $propDef): string
    {
        if (!$propDef->hasReference()) {
            return '';
        }

        $desc = $propDef->getReference()->getTargetModel();

        if ($propDef->getReference()->getReverseProperty() !== null) {
            $desc .= '.' . $propDef->getReference()->getReverseProperty();
        }

        if (count($propDef->getReference()->getPath()) > 0) {
            $desc .= ' (via ';
            $desc .= implode(', ', array_map(function(PropertyPathDefinition $path) {
                $desc = $path->getTargetModel();
                if ($path->getProperty() !== null) {
                    $desc .= '.' . $path->getProperty();
                }
                if ($path->getForwardProperty() !== null) {
                    $desc .= ' on ' . $path->getTargetModel() . '.' . $path->getForwardProperty();
                }
                return $desc;
            }, $propDef->getReference()->getPath()));
            $desc .= ')';
        }

        if ($propDef->getReference()->getSelectProperty() !== null) {
            $desc .= ' @ ' . $propDef->getReference()->getSelectProperty();
        }

        return $desc;
    }

    private function getRelationshipDescription(ModelServiceInterface $modelService, PropertyDefinition $propDef): string
    {
        if (!$propDef->hasReference()) {
            return '';
        }

        return (string) PropertyReferenceHelper::getAssociationCardinality($modelService, $propDef);
    }

    protected function getApp(): ApplicationInterface
    {
        return $this->getContainer()->get(ApplicationInterface::class);
    }
}
