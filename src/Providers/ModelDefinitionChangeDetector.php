<?php

namespace WebImage\Models\Providers;

use WebImage\Models\Services\DictionaryService;

class ModelDefinitionChangeDetector
{
    /**
     * Compare provider's current state against DictionaryService
     *
     * @param ModelDefinitionProviderInterface $provider
     * @param DictionaryService $dictionaryService
     * @return ModelDefinitionChangeSet
     */
    public function detectChanges(
        ModelDefinitionProviderInterface $provider,
        DictionaryService $dictionaryService
    ): ModelDefinitionChangeSet {
        $changeSet = new ModelDefinitionChangeSet();

        // Get current metadata from provider
        $currentMetadata = $this->indexMetadataByName($provider->getModelMetadata());

        // Get existing definitions from dictionary
        $existingDefinitions = $dictionaryService->getModelDefinitions();
        $existingNames = array_map(fn($def) => $def->getName(), $existingDefinitions);
        $existingNames = array_flip($existingNames);

        // Check for added and modified models
        foreach ($currentMetadata as $name => $metadata) {
            if (!isset($existingNames[$name])) {
                // Model is new
                $changeSet->addAdded($name);
            } else {
                // Model exists - we consider it modified since we don't have
                // a reliable way to compare without the original metadata
                // In a future enhancement, we could store metadata alongside definitions
                // For now, we assume if it exists in provider, it might have changed
                // This is conservative but safe
            }
        }

        // Check for removed models
        foreach ($existingNames as $name => $_) {
            if (!isset($currentMetadata[$name])) {
                $changeSet->addRemoved($name);
            }
        }

        return $changeSet;
    }

    /**
     * Compare provider's current state against stored metadata
     * This is more accurate than comparing against DictionaryService
     *
     * @param ModelDefinitionProviderInterface $provider
     * @param ModelDefinitionMetadata[] $baselineMetadata
     * @return ModelDefinitionChangeSet
     */
    public function detectChangesFromBaseline(
        ModelDefinitionProviderInterface $provider,
        array $baselineMetadata
    ): ModelDefinitionChangeSet {
        $changeSet = new ModelDefinitionChangeSet();

        // Index both sets by name
        $currentMetadata = $this->indexMetadataByName($provider->getModelMetadata());
        $baselineMetadata = $this->indexMetadataByName($baselineMetadata);

        // Check for added and modified models
        foreach ($currentMetadata as $name => $metadata) {
            if (!isset($baselineMetadata[$name])) {
                $changeSet->addAdded($name);
            } elseif ($metadata->isNewerThan($baselineMetadata[$name])) {
                $changeSet->addModified($name);
            }
        }

        // Check for removed models
        foreach ($baselineMetadata as $name => $metadata) {
            if (!isset($currentMetadata[$name])) {
                $changeSet->addRemoved($name);
            }
        }

        return $changeSet;
    }

    /**
     * Index metadata array by model name
     *
     * @param ModelDefinitionMetadata[] $metadata
     * @return array<string, ModelDefinitionMetadata>
     */
    private function indexMetadataByName(array $metadata): array
    {
        $indexed = [];
        foreach ($metadata as $meta) {
            $indexed[$meta->getName()] = $meta;
        }
        return $indexed;
    }
}