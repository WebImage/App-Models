<?php

namespace WebImage\Models\Actions;

use WebImage\Models\Providers\ModelDefinitionChangeSet;
use WebImage\Models\Services\RepositoryInterface;

/**
 * Action to import model definitions into the database
 */
class ImportModelsAction implements ModelActionInterface
{
    private RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(
        ModelDefinitionChangeSet $changes,
        array $options = [],
        ?ProgressHandlerInterface $progress = null
    ): ModelActionResult {
        // Use null handler if none provided
        $progress = $progress ?? new NullProgressHandler();
        $result = ModelActionResult::success();

        $modelDefs = $this->repository->getDictionaryService()->getModelDefinitions();

        // Filter by limit-model option if provided
        $limitModels = $options['limit-model'] ?? null;
        if ($limitModels) {
            $limitModelNames = array_map('trim', explode(',', $limitModels));
            $modelDefs = array_filter($modelDefs, function($modelDef) use ($limitModelNames) {
                return in_array($modelDef->getName(), $limitModelNames);
            });
        }

        $saveModels = [];

        // Create all model instances so that they can reference one another on save
        foreach ($modelDefs as $modelDef) {
            $model = $this->repository->getModelService()->getModel($modelDef->getName());

            if ($model === null) {
                $model = $this->repository->getModelService()->create(
                    $modelDef->getName(),
                    $modelDef->getPluralName(),
                    $modelDef->getFriendlyName(),
                    $modelDef->getFriendlyName()
                );
            }

            $model->setDef($modelDef);
            $saveModels[] = $model;
        }

        $count = count($saveModels);
        $message = "Importing {$count} model(s)...";
        $progress->info($message);
        $result->addInfo($message);

        try {
            $importedModels = [];
            $currentIndex = 0;

            foreach ($saveModels as $saveModel) {
                $currentIndex++;
                $modelName = $saveModel->getDef()->getName();

                // Report progress
                $progress->progress($currentIndex, $count, "Importing: {$modelName}");

                $importMessage = "  Importing: {$modelName}";
                $progress->info($importMessage);
                $result->addInfo($importMessage);

                $saveModel->save();
                $importedModels[] = $modelName;
            }

            $completeMessage = "Import complete at " . date('Y-m-d H:i:s');
            $progress->info($completeMessage);
            $result->addInfo($completeMessage);

            // Set result data
            $result->setData('imported_count', $count);
            $result->setData('imported_models', $importedModels);

            return $result;
        } catch (\Exception $e) {
            $errorMessage = "Import failed: " . $e->getMessage();
            $progress->error($errorMessage);
            return ModelActionResult::failure($errorMessage, $e);
        }
    }

    public function getDescription(): string
    {
        return 'Import model definitions into database';
    }

    public function shouldRun(ModelDefinitionChangeSet $changes): bool
    {
        // Run if there are any changes
        return $changes->hasChanges();
    }
}
