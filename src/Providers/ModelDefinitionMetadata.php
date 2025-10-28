<?php

namespace WebImage\Models\Providers;

use DateTimeInterface;

class ModelDefinitionMetadata
{
    private string $name;
    private string $source;
    private ?DateTimeInterface $lastModified;
    private ?string $hash;

    public function __construct(
        string             $name,
        string             $source,
        ?DateTimeInterface $lastModified = null,
        ?string            $hash = null
    ) {
        $this->name = $name;
        $this->source = $source;
        $this->lastModified = $lastModified;
        $this->hash = $hash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->lastModified;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * Check if this metadata represents a newer version than another
     */
    public function isNewerThan(?ModelDefinitionMetadata $other): bool
    {
        if ($other === null) {
            return true;
        }

        // First try hash comparison (most reliable)
        if ($this->hash !== null && $other->hash !== null) {
            return $this->hash !== $other->hash;
        }

        // Fall back to timestamp comparison
        if ($this->lastModified !== null && $other->lastModified !== null) {
            return $this->lastModified > $other->lastModified;
        }

        return false;
    }
}