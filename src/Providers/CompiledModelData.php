<?php

namespace WebImage\Models\Providers;

use DateTimeInterface;

class CompiledModelData
{
    public DateTimeInterface $compiledAt;
    public string $definitionsHash;
    /** @var SourceFileMetadata[] */
    public array $sourceFiles;
    public array $definitions;
    /**
     * @param DateTimeInterface $compiledAt When this compilation was created
     * @param string $definitionsHash Aggregate hash of all model definitions
     * @param SourceFileMetadata[] $sourceFiles Metadata about source YAML files
     * @param array $definitions Array representation of model definitions
     */
    public function __construct(
        /* public readonly */ DateTimeInterface $compiledAt,
        /* public readonly */ string $definitionsHash,
        /* public readonly */ array $sourceFiles,
        /* public readonly */ array $definitions
    ) {
        $this->compiledAt = $compiledAt;
        $this->definitionsHash = $definitionsHash;
        $this->sourceFiles = $sourceFiles;
        $this->definitions = $definitions;
    }
}