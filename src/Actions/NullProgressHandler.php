<?php

namespace WebImage\Models\Actions;

/**
 * Null implementation of ProgressHandlerInterface
 * Used when no progress reporting is needed (e.g., in API calls, tests)
 */
class NullProgressHandler implements ProgressHandlerInterface
{
    public function info(string $message): void
    {
        // Do nothing
    }

    public function warning(string $message): void
    {
        // Do nothing
    }

    public function error(string $message): void
    {
        // Do nothing
    }

    public function progress(int $current, int $total, ?string $message = null): void
    {
        // Do nothing
    }
}