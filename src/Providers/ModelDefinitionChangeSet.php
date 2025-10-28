<?php

namespace WebImage\Models\Providers;

class ModelDefinitionChangeSet
{
    private array $added = [];
    private array $modified = [];
    private array $removed = [];

    /**
     * Add a model that was added
     */
    public function addAdded(string $modelName): void
    {
        $this->added[] = $modelName;
    }

    /**
     * Add a model that was modified
     */
    public function addModified(string $modelName): void
    {
        $this->modified[] = $modelName;
    }

    /**
     * Add a model that was removed
     */
    public function addRemoved(string $modelName): void
    {
        $this->removed[] = $modelName;
    }

    /**
     * Check if there are any changes
     */
    public function hasChanges(): bool
    {
        return !empty($this->added) || !empty($this->modified) || !empty($this->removed);
    }

    /**
     * Get list of added models
     *
     * @return string[]
     */
    public function getAdded(): array
    {
        return $this->added;
    }

    /**
     * Get list of modified models
     *
     * @return string[]
     */
    public function getModified(): array
    {
        return $this->modified;
    }

    /**
     * Get list of removed models
     *
     * @return string[]
     */
    public function getRemoved(): array
    {
        return $this->removed;
    }

    /**
     * Get total count of changes
     */
    public function getChangeCount(): int
    {
        return count($this->added) + count($this->modified) + count($this->removed);
    }

    /**
     * Get all changed model names (added + modified + removed)
     *
     * @return string[]
     */
    public function getAllChangedModels(): array
    {
        return array_merge($this->added, $this->modified, $this->removed);
    }
}