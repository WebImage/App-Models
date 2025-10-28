<?php

namespace WebImage\Models\Actions;

/**
 * Encapsulates the result of a model action execution
 * This allows actions to be used in any context (CLI, API, background jobs, etc.)
 */
class ModelActionResult
{
    private bool $success;
    private array $messages = [];
    private array $data = [];
    private ?\Exception $exception = null;

    private function __construct(bool $success)
    {
        $this->success = $success;
    }

    /**
     * Create a successful result
     */
    public static function success(array $data = []): self
    {
        $result = new self(true);
        $result->data = $data;
        return $result;
    }

    /**
     * Create a failed result
     */
    public static function failure(string $errorMessage, ?\Exception $exception = null): self
    {
        $result = new self(false);
        $result->addError($errorMessage);
        $result->exception = $exception;
        return $result;
    }

    /**
     * Check if the action was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the action failed
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Add an informational message
     */
    public function addInfo(string $message): void
    {
        $this->messages[] = [
            'level' => 'info',
            'message' => $message
        ];
    }

    /**
     * Add a warning message
     */
    public function addWarning(string $message): void
    {
        $this->messages[] = [
            'level' => 'warning',
            'message' => $message
        ];
    }

    /**
     * Add an error message
     */
    public function addError(string $message): void
    {
        $this->messages[] = [
            'level' => 'error',
            'message' => $message
        ];
    }

    /**
     * Get all messages
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get messages by level
     */
    public function getMessagesByLevel(string $level): array
    {
        return array_filter($this->messages, function($message) use ($level) {
            return $message['level'] === $level;
        });
    }

    /**
     * Get just the message text (without levels)
     */
    public function getMessageText(): array
    {
        return array_map(function($message) {
            return $message['message'];
        }, $this->messages);
    }

    /**
     * Set data value
     */
    public function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get data value or all data
     */
    public function getData(?string $key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Get the exception if one occurred
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    /**
     * Convert to array (useful for JSON responses)
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'messages' => $this->messages,
            'data' => $this->data,
            'error' => $this->exception ? $this->exception->getMessage() : null
        ];
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}