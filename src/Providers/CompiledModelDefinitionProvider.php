<?php

namespace WebImage\Models\Providers;

use WebImage\Models\Compiler\ModelDefinitionHydrator;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
//use WebImage\Models\Defs\Property;
//use WebImage\Models\Types\Type;

class CompiledModelDefinitionProvider implements ModelDefinitionProviderInterface
{
    private string $compiledFilePath;
    private ?CompiledModelData $compiledData = null;
    private ?array $cachedDefinitions = null;
    private ?array $cachedMetadata = null;

    public function __construct(string $compiledFilePath)
    {
        $this->compiledFilePath = $compiledFilePath;
    }

    /**
     * Load the compiled data
     */
    public function load(): CompiledModelData
    {
        if ($this->compiledData === null) {
            
            if (!file_exists($this->compiledFilePath)) {
                throw new \RuntimeException("Compiled models file not found: {$this->compiledFilePath}");
            }
            
            $raw = include $this->compiledFilePath;
            $this->compiledData = $this->hydrate($raw);
        }

        return $this->compiledData;
    }

    /**
     * @inheritDoc
     */
    public function getModelMetadata(): array
    {
        if ($this->cachedMetadata === null) {
            $data = $this->load();
            $this->cachedMetadata = [];

            foreach ($data->definitions as $name => $defArray) {
                // Use source file metadata if available
                $sourceFile = $this->findSourceFileForModel($name, $data->sourceFiles);

                $this->cachedMetadata[] = new ModelDefinitionMetadata(
                    $name,
                    $sourceFile ? $sourceFile->path : 'compiled',
                    $sourceFile ? $sourceFile->modifiedAt : $data->compiledAt,
                    $sourceFile ? $sourceFile->hash : $data->definitionsHash
                );
            }
        }

        return $this->cachedMetadata;
    }

    /**
     * @inheritDoc
     */
    public function getModelDefinition(string $name): ?ModelDefinitionInterface
    {
        $all = $this->getAllModelDefinitions();
        return $all[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getAllModelDefinitions(): array
    {
        if ($this->cachedDefinitions === null) {
            $data = $this->load();
            $this->cachedDefinitions = $this->hydrateDefinitions($data->definitions);
        }

        return $this->cachedDefinitions;
    }

    public function reload(): void
    {
        $this->cachedDefinitions = null;
        $this->compiledData = null;
    }

    /**
     * Hydrate raw array data into CompiledModelData object
     */
    private function hydrate(array $raw): CompiledModelData
    {
        return new CompiledModelData(
            /*compiledAt: */new \DateTime($raw['compiled_at']),
            /*definitionsHash:*/ $raw['definitions_hash'],
            /*sourceFiles:*/ $this->hydrateSourceFiles($raw['source_files'] ?? []),
            /*definitions: */$raw['definitions']
        );
    }

    /**
     * Hydrate source file metadata
     *
     * @return SourceFileMetadata[]
     */
    private function hydrateSourceFiles(array $sourceFilesData): array
    {
        $result = [];
        foreach ($sourceFilesData as $path => $data) {
            $result[] = new SourceFileMetadata(
                $path,
                $data['hash'],
                new \DateTime($data['modified'])
            );
        }
        return $result;
    }

    /**
     * Convert array representations back to ModelDefinition objects
     *
     * @return ModelDefinitionInterface[]
     */
    private function hydrateDefinitions(array $definitionsData): array
    {
        $hydrator = new ModelDefinitionHydrator();

        return $hydrator->hydrateModelDefinitions($definitionsData);
    }

    /**
     * Find which source file contains a given model
     */
    private function findSourceFileForModel(string $modelName, array $sourceFiles): ?SourceFileMetadata
    {
        // For now, we can't determine which specific source file a model came from
        // without additional metadata. Return null and use compiled metadata instead.
        // This could be enhanced by storing model->file mapping in compiled data.
        return null;
    }

    /**
     * Get the definitions hash
     */
    public function getDefinitionsHash(): string
    {
        return $this->load()->definitionsHash;
    }
}