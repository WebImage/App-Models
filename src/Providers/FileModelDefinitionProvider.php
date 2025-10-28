<?php

namespace WebImage\Models\Providers;

use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Helpers\DictionaryTypeHelper;

class FileModelDefinitionProvider implements ModelDefinitionProviderInterface
{
    private array $modelFiles;
    private ?array $variables;
    private ?array $cachedMetadata = null;
    private ?array $cachedDefinitions = null;

    /**
     * @param array $modelFiles Array of file paths to YAML model definition files
     * @param array|null $variables Optional variables for interpolation in YAML files
     */
    public function __construct(array $modelFiles, ?array $variables = null)
    {
        $this->modelFiles = $modelFiles;
        $this->variables = $variables;
    }

    /**
     * @inheritDoc
     */
    public function getModelMetadata(): array
    {
        if ($this->cachedMetadata !== null) {
            return $this->cachedMetadata;
        }

        $metadata = [];

        foreach ($this->modelFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            // Load models from this file to get their names
            $models = DictionaryTypeHelper::load($file, $this->variables);

            $lastModified = new \DateTime();
            $lastModified->setTimestamp(filemtime($file));

            // Create hash of file content for reliable change detection
            $hash = md5_file($file);

            foreach ($models as $model) {
                $metadata[] = new ModelDefinitionMetadata(
                    $model->getName(),
                    $file,
                    $lastModified,
                    $hash
                );
            }
        }

        $this->cachedMetadata = $metadata;
        return $metadata;
    }

    /**
     * @inheritDoc
     */
    public function getModelDefinition(string $name): ?ModelDefinitionInterface
    {
        // If we have cached definitions, check there first
        if ($this->cachedDefinitions !== null) {
            return $this->cachedDefinitions[$name] ?? null;
        }

        // Otherwise, load all definitions and find the one we need
        $allDefinitions = $this->getAllModelDefinitions();
        return $allDefinitions[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAllModelDefinitions(): array
    {
        if ($this->cachedDefinitions !== null) {
            return $this->cachedDefinitions;
        }

        $definitions = [];

        foreach ($this->modelFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $models = DictionaryTypeHelper::load($file, $this->variables);

            foreach ($models as $model) {
                $definitions[$model->getName()] = $model;
            }
        }

        $this->cachedDefinitions = $definitions;
        return $definitions;
    }

    /**
     * Get the list of files being watched
     *
     * @return array
     */
    public function getModelFiles(): array
    {
        return $this->modelFiles;
    }

    /**
     * Clear internal cache
     * Call this when you know files have changed
     */
    public function clearCache(): void
    {
        $this->cachedMetadata = null;
        $this->cachedDefinitions = null;
    }
}