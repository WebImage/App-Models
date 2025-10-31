<?php

namespace WebImage\Models\Providers;

use WebImage\Models\Defs\ModelDefinitionInterface;

interface ModelDefinitionProviderInterface
{
    /**
     * Get metadata for all available models (lightweight operation)
     *
     * @return ModelDefinitionMetadata[]
     */
    public function getModelMetadata(): array;

    /**
     * Load a specific model definition by name
     *
     * @param string $name The model name
     * @return ModelDefinitionInterface|null
     */
    public function getModelDefinition(string $name): ?ModelDefinitionInterface;

    /**
     * Load all model definitions at once
     * Useful for initial loading into DictionaryService
     *
     * @return ModelDefinitionInterface[]
     */
    public function getAllModelDefinitions(): array;
    /**
     * Force provider to reload all model definitions.
     * Useful for when definitions may have been updated externall
     *
     * @return void
     */
    public function reload(): void;
}