<?php

namespace WebImage\Models\Actions;

use WebImage\Models\Providers\ModelDefinitionChangeSet;

/**
 * Interface for actions that can be performed when model definitions change
 *
 * Actions now return ModelActionResult instead of bool, and accept an optional
 * ProgressHandlerInterface for real-time feedback. This makes actions usable
 * in any context (CLI, API, background jobs, tests) without coupling to console output.
 */
interface ModelActionInterface
{
    /**
     * Execute the action
     *
     * @param ModelDefinitionChangeSet $changes The detected changes
     * @param array $options Additional options for the action
     * @param ProgressHandlerInterface|null $progress Optional progress handler for real-time updates
     * @return ModelActionResult Result object with success/failure status and messages
     */
    public function execute(
        ModelDefinitionChangeSet $changes,
        array $options = [],
        ?ProgressHandlerInterface $progress = null
    ): ModelActionResult;

    /**
     * Get a description of what this action does
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Check if this action should run based on the changes detected
     * For example, some actions might only need to run if models were added/modified, not removed
     *
     * @param ModelDefinitionChangeSet $changes
     * @return bool
     */
    public function shouldRun(ModelDefinitionChangeSet $changes): bool;
}