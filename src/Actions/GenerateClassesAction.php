<?php

namespace WebImage\Models\Actions;

use WebImage\Application\ApplicationInterface;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Providers\ModelDefinitionChangeSet;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Models\Templates\TemplateRenderer;

/**
 * Action to generate entity and repository classes from model definitions
 */
class GenerateClassesAction implements ModelActionInterface
{
    private RepositoryInterface $repository;
    private ApplicationInterface $application;
    private ?string $baseNamespace;
    private ?string $templateDir;
    private ?ProgressHandlerInterface $progress;
    private ?ModelActionResult $result;

    public function __construct(
        RepositoryInterface $repository,
        ApplicationInterface $application,
        ?string $baseNamespace = null,
        ?string $templateDir = null
    ) {
        $this->repository = $repository;
        $this->application = $application;
        $this->baseNamespace = $baseNamespace;
        $this->templateDir = $templateDir;
    }

    public function execute(
        ModelDefinitionChangeSet $changes,
        array $options = [],
        ?ProgressHandlerInterface $progress = null
    ): ModelActionResult {
        // Use null handler if none provided
        $this->progress = $progress ?? new NullProgressHandler();
        $this->result = ModelActionResult::success();

        $outputDir = $options['output-dir'] ?? $this->application->getProjectPath() . '/src/Models';
        $outputDir = rtrim($outputDir, '/\\');

        $force = $options['force'] ?? false;

        $templateDir = $options['template-dir'] ?? $this->templateDir ?? $this->application->getProjectPath() . '/src/Models/Templates';
        $renderer = new TemplateRenderer($templateDir);

        $models = $this->repository->getModelService()->all();
        $count = count($models);
        $message = "Generating classes for {$count} model(s)...";
        $this->progress->info($message);
        $this->result->addInfo($message);

        try {
            $generatedFiles = [];
            $skippedFiles = [];
            $currentIndex = 0;

            foreach ($models as $model) {
                $currentIndex++;
                $modelName = $model->getDef()->getName();

                // Report progress
                $this->progress->progress($currentIndex, $count, "Processing: {$modelName}");

                $processMessage = "Processing: {$modelName}";
                $this->progress->info($processMessage);
                $this->result->addInfo($processMessage);

                $modelResult = $this->processModelDef($model->getDef(), $renderer, $outputDir, $force);
                $generatedFiles = array_merge($generatedFiles, $modelResult['generated']);
                $skippedFiles = array_merge($skippedFiles, $modelResult['skipped']);
            }

            $completeMessage = "Class generation complete!";
            $this->progress->info($completeMessage);
            $this->result->addInfo($completeMessage);

            // Set result data
            $this->result->setData('generated_count', count($generatedFiles));
            $this->result->setData('skipped_count', count($skippedFiles));
            $this->result->setData('generated_files', $generatedFiles);
            $this->result->setData('skipped_files', $skippedFiles);

            return $this->result;
        } catch (\Exception $e) {
            $errorMessage = "Class generation failed: " . $e->getMessage();
            $this->progress->error($errorMessage);
            return ModelActionResult::failure($errorMessage, $e);
        }
    }

    public function getDescription(): string
    {
        return 'Generate entity and repository classes';
    }

    public function shouldRun(ModelDefinitionChangeSet $changes): bool
    {
        // Run if there are any changes
        return $changes->hasChanges();
    }

    private function processModelDef(
        ModelDefinition $modelDef,
        TemplateRenderer $renderer,
        string $outputDir,
        bool $force
    ): array {
        $generated = [];
        $skipped = [];

        $entityResult = $this->generateEntityClasses($modelDef, $renderer, $outputDir, $force);
        $generated = array_merge($generated, $entityResult['generated']);
        $skipped = array_merge($skipped, $entityResult['skipped']);

        $repoResult = $this->generateRepositoryClasses($modelDef, $renderer, $outputDir, $force);
        $generated = array_merge($generated, $repoResult['generated']);
        $skipped = array_merge($skipped, $repoResult['skipped']);

        return [
            'generated' => $generated,
            'skipped' => $skipped
        ];
    }

    private function generateEntityClasses(
        ModelDefinition $modelDef,
        TemplateRenderer $renderer,
        string $outputDir,
        bool $force
    ): array {
        $generated = [];
        $skipped = [];

        // Generate base entity class (always overwritten)
        $baseClassContent = $this->generateBaseEntityClass($modelDef, $renderer);
        $baseClassPath = $this->getBaseEntityPath($outputDir, $modelDef);

        $this->writeFile($baseClassPath, $baseClassContent, true);
        $message = "  Generated: {$baseClassPath}";
        $this->progress->info($message);
        $this->result->addInfo($message);
        $generated[] = $baseClassPath;

        // Generate entity class (only if it doesn't exist or force is true)
        $entityClassPath = $this->getEntityPath($outputDir, $modelDef);
        if (!file_exists($entityClassPath) || $force) {
            $entityClassContent = $this->generateEntityClass($modelDef, $renderer);
            $this->writeFile($entityClassPath, $entityClassContent, $force);
            $message = "  Generated: {$entityClassPath}";
            $this->progress->info($message);
            $this->result->addInfo($message);
            $generated[] = $entityClassPath;
        } else {
            $message = "  Skipped (exists): {$entityClassPath}";
            $this->progress->info($message);
            $this->result->addInfo($message);
            $skipped[] = $entityClassPath;
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped
        ];
    }

    private function generateRepositoryClasses(
        ModelDefinition $modelDef,
        TemplateRenderer $renderer,
        string $outputDir,
        bool $force
    ): array {
        $generated = [];
        $skipped = [];

        // Generate base repository class (always overwritten)
        $baseRepoContent = $this->generateBaseRepositoryClass($modelDef, $renderer);
        $baseRepoPath = $this->getBaseRepositoryPath($outputDir, $modelDef);
        $this->writeFile($baseRepoPath, $baseRepoContent, true);
        $message = "  Generated: {$baseRepoPath}";
        $this->progress->info($message);
        $this->result->addInfo($message);
        $generated[] = $baseRepoPath;

        // Generate repository class (only if it doesn't exist or force is true)
        $repoClassPath = $this->getRepositoryPath($outputDir, $modelDef);
        if (!file_exists($repoClassPath) || $force) {
            $repoClassContent = $this->generateRepositoryClass($modelDef, $renderer);
            $this->writeFile($repoClassPath, $repoClassContent, $force);
            $message = "  Generated: {$repoClassPath}";
            $this->progress->info($message);
            $this->result->addInfo($message);
            $generated[] = $repoClassPath;
        } else {
            $message = "  Skipped (exists): {$repoClassPath}";
            $this->progress->info($message);
            $this->result->addInfo($message);
            $skipped[] = $repoClassPath;
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped
        ];
    }

    private function generateBaseEntityClass(ModelDefinition $modelDef, TemplateRenderer $renderer): string
    {
        $className = $this->getEntityClassName($modelDef);
        $baseClassName = $className . 'Base';
        $namespace = $this->baseNamespace . '\\Entities\\Generated';

        $methods = [];
        foreach ($modelDef->getProperties() as $propDef) {
            $propertyMethods = $this->generatePropertyMethods($propDef);
            $methods = array_merge($methods, $propertyMethods);
        }

        $methodsString = implode("\n\n", $methods);

        return $renderer->render('BaseEntity', [
            'generatedDate' => date('Y-m-d H:i:s'),
            'modelName' => $modelDef->getName(),
            'namespace' => $namespace,
            'className' => $baseClassName,
            'methods' => $methodsString
        ]);
    }

    private function generateEntityClass(ModelDefinition $modelDef, TemplateRenderer $renderer): string
    {
        $className = $this->getEntityClassName($modelDef);
        $baseClassName = $className . 'Base';
        $namespace = $this->baseNamespace . '\\Entities';
        $baseNamespace = $this->baseNamespace . '\\Entities\\Generated';

        return $renderer->render('Entity', [
            'namespace' => $namespace,
            'baseNamespace' => $baseNamespace,
            'className' => $className,
            'baseClassName' => $baseClassName
        ]);
    }

    private function generateBaseRepositoryClass(ModelDefinition $modelDef, TemplateRenderer $renderer): string
    {
        $entityClassName = $this->getEntityClassName($modelDef);
        $repoClassName = $this->getRepositoryClassName($modelDef);
        $baseRepoClassName = $repoClassName . 'Base';
        $namespace = $this->baseNamespace . '\\Repositories\\Generated';
        $entityNamespace = $this->baseNamespace . '\\Entities';

        $modelName = $modelDef->getName();
        $primaryKeys = $modelDef->getPrimaryKeys()->keys();
        $primaryKeyType = count($primaryKeys) === 1 ? $this->getPhpType($modelDef->getProperty($primaryKeys[0])) : 'array';

        return $renderer->render('BaseRepository', [
            'generatedDate' => date('Y-m-d H:i:s'),
            'modelName' => $modelName,
            'namespace' => $namespace,
            'entityNamespace' => $entityNamespace,
            'className' => $baseRepoClassName,
            'entityClassName' => $entityClassName,
            'primaryKeyType' => $primaryKeyType
        ]);
    }

    private function generateRepositoryClass(ModelDefinition $modelDef, TemplateRenderer $renderer): string
    {
        $repoClassName = $this->getRepositoryClassName($modelDef);
        $baseRepoClassName = $repoClassName . 'Base';
        $namespace = $this->baseNamespace . '\\Repositories';
        $baseNamespace = $this->baseNamespace . '\\Repositories\\Generated';

        return $renderer->render('Repository', [
            'namespace' => $namespace,
            'baseNamespace' => $baseNamespace,
            'className' => $repoClassName,
            'baseClassName' => $baseRepoClassName
        ]);
    }

    private function generatePropertyMethods(PropertyDefinition $propDef): array
    {
        $methods = [];
        $propertyName = $propDef->getName();
        $methodName = ucfirst($propertyName);
        $phpType = $this->getPhpType($propDef);
        $isNullable = !$propDef->isRequired();
        $nullablePrefix = $isNullable ? '?' : '';

        // Getter
        $getter = "    public function get{$methodName}(): {$nullablePrefix}{$phpType}
    {
        return \$this->entity['{$propertyName}'];
    }";

        // Setter
        $setter = "    public function set{$methodName}({$nullablePrefix}{$phpType} \${$propertyName}): void
    {
        \$this->entity['{$propertyName}'] = \${$propertyName};
    }";

        $methods[] = $getter;
        $methods[] = $setter;

        return $methods;
    }

    private function getPhpType(PropertyDefinition $propDef): string
    {
        if ($propDef->isVirtual() && $propDef->hasReference()) {
            $targetModel = $propDef->getReference()->getTargetModel();
            $entityClassName = $this->modelNameToClassName($targetModel) . 'Entity';

            if ($propDef->isMultiValued()) {
                return 'array';
            }

            return $this->baseNamespace . '\\Entities\\' . $entityClassName;
        }

        $dataType = $propDef->getDataType();

        if ($propDef->isMultiValued()) {
            return 'array';
        }

        switch($dataType) {
            case 'integer':
                return 'int';
            case 'decimal':
                return 'float';
            case 'boolean':
                return 'bool';
            case 'date':
            case 'datetime':
                return '\\DateTime';
            case 'string':
            case 'text':
                return 'string';
            default:
                return 'mixed';
        }
    }

    private function getEntityClassName(ModelDefinition $modelDef): string
    {
        return $this->modelNameToClassName($modelDef->getName()) . 'Entity';
    }

    private function getRepositoryClassName(ModelDefinition $modelDef): string
    {
        return $this->modelNameToClassName($modelDef->getName()) . 'Repository';
    }

    private function modelNameToClassName(string $modelName): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $modelName)));
    }

    private function getEntityPath(string $outputDir, ModelDefinition $modelDef): string
    {
        $className = $this->getEntityClassName($modelDef);
        return $outputDir . '/Entities/' . $className . '.php';
    }

    private function getBaseEntityPath(string $outputDir, ModelDefinition $modelDef): string
    {
        $className = $this->getEntityClassName($modelDef) . 'Base';
        return $outputDir . '/Entities/Generated/' . $className . '.php';
    }

    private function getRepositoryPath(string $outputDir, ModelDefinition $modelDef): string
    {
        $className = $this->getRepositoryClassName($modelDef);
        return $outputDir . '/Repositories/' . $className . '.php';
    }

    private function getBaseRepositoryPath(string $outputDir, ModelDefinition $modelDef): string
    {
        $className = $this->getRepositoryClassName($modelDef) . 'Base';
        return $outputDir . '/Repositories/Generated/' . $className . '.php';
    }

    private function writeFile(string $path, string $content, bool $overwrite = false): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!file_exists($path) || $overwrite) {
            file_put_contents($path, $content);
        }
    }
}
