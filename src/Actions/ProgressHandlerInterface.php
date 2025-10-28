<?php

namespace WebImage\Models\Actions;

/**
 * Interface for handling progress updates during action execution
 * Implementations can send updates to different destinations (console, logs, webhooks, etc.)
 */
interface ProgressHandlerInterface
{
    /**
     * Report an informational message
     */
    public function info(string $message): void;

    /**
     * Report a warning message
     */
    public function warning(string $message): void;

    /**
     * Report an error message
     */
    public function error(string $message): void;

    /**
     * Report progress with current/total counts
     *
     * @param int $current Current count (e.g., 3)
     * @param int $total Total count (e.g., 10)
     * @param string|null $message Optional message (e.g., "Processing item 3")
     */
    public function progress(int $current, int $total, ?string $message = null): void;
}