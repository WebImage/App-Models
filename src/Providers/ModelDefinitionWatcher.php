<?php

namespace WebImage\Models\Providers;

use WebImage\Files\FileWatcher;

/**
 * Watches for changes to model definition files and triggers callbacks
 * This decouples file watching from commands
 */
class ModelDefinitionWatcher
{
    private FileModelDefinitionProvider $provider;
    private FileWatcher $fileWatcher;
    private ?array $baselineMetadata = null;

    public function __construct(FileModelDefinitionProvider $provider)
    {
        $this->provider = $provider;
        $this->fileWatcher = new FileWatcher();

        // Add all model files to the watcher
        $this->fileWatcher->addFiles($provider->getModelFiles());
    }

    /**
     * Watch for file changes and execute callback when changes detected
     *
     * @param callable $callback Function to call with ModelDefinitionChangeSet when changes detected
     * @param int $interval Check interval in seconds
     * @param callable|null $onStart Optional callback to run before watching starts
     */
    public function watch(callable $callback, int $interval = 1, ?callable $onStart = null): void
    {
        // Capture baseline on first run
        $this->baselineMetadata = $this->provider->getModelMetadata();

        if ($onStart) {
            $onStart();
        }

        $this->fileWatcher->watch(
            function(array $changedFiles) use ($callback) {
                // Clear provider cache so it re-reads files
                $this->provider->clearCache();

                // Detect changes
                $detector = new ModelDefinitionChangeDetector();
                $changes = $detector->detectChangesFromBaseline(
                    $this->provider,
                    $this->baselineMetadata
                );

                if ($changes->hasChanges()) {
                    // Update baseline
                    $this->baselineMetadata = $this->provider->getModelMetadata();

                    // Notify callback
                    $callback($changes, $changedFiles);
                }
            },
            $interval
        );
    }

    /**
     * Check for changes once without watching continuously
     *
     * @return ModelDefinitionChangeSet
     */
    public function checkOnce(): ModelDefinitionChangeSet
    {
        $changedFiles = $this->fileWatcher->checkForChanges();

        if (empty($changedFiles)) {
            return new ModelDefinitionChangeSet();
        }

        // Clear provider cache so it re-reads files
        $this->provider->clearCache();

        // If we don't have a baseline, capture current state as baseline
        // and report no changes (first run scenario)
        if ($this->baselineMetadata === null) {
            $this->baselineMetadata = $this->provider->getModelMetadata();
            return new ModelDefinitionChangeSet();
        }

        // Detect changes
        $detector = new ModelDefinitionChangeDetector();
        $changes = $detector->detectChangesFromBaseline(
            $this->provider,
            $this->baselineMetadata
        );

        if ($changes->hasChanges()) {
            // Update baseline
            $this->baselineMetadata = $this->provider->getModelMetadata();
        }

        return $changes;
    }

    /**
     * Get the underlying FileWatcher
     *
     * @return FileWatcher
     */
    public function getFileWatcher(): FileWatcher
    {
        return $this->fileWatcher;
    }
}