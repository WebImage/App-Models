<?php

namespace WebImage\Models\Actions;

use WebImage\Console\OutputInterface;

/**
 * Progress handler that writes to console output
 * Used in CLI commands to provide real-time feedback
 */
class ConsoleProgressHandler implements ProgressHandlerInterface
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function info(string $message): void
    {
        $this->output->info($message);
    }

    public function warning(string $message): void
    {
        $this->output->warning($message);
    }

    public function error(string $message): void
    {
        $this->output->error($message);
    }

    public function progress(int $current, int $total, ?string $message = null): void
    {
        $percentage = ($total > 0) ? round(($current / $total) * 100) : 0;
        $progressMsg = "Progress: {$current}/{$total} ({$percentage}%)";

        if ($message !== null) {
            $progressMsg .= " - {$message}";
        }

        $this->output->writeln($progressMsg);
    }
}