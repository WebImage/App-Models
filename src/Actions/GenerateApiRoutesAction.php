<?php

/**
 * @TODO NOT IMPLEMENTED
 */
namespace WebImage\Models\Actions;

use WebImage\Models\Providers\ModelDefinitionChangeSet;
use WebImage\Models\Services\RepositoryInterface;

/**
 * Example action to generate API routes from model definitions
 * This demonstrates how easy it is to add new actions to the sync process
 */
class GenerateApiRoutesAction implements ModelActionInterface
{
    private RepositoryInterface $repository;
    private string $routesOutputPath;

    public function __construct(RepositoryInterface $repository, string $routesOutputPath)
    {
        $this->repository = $repository;
        $this->routesOutputPath = $routesOutputPath;
    }

    public function execute(
        ModelDefinitionChangeSet $changes,
        array $options = [],
        ?ProgressHandlerInterface $progress = null
    ): ModelActionResult {
        // Use null handler if none provided
        $progress = $progress ?? new NullProgressHandler();
        $result = ModelActionResult::success();

        $modelDefs = $this->repository->getDictionaryService()->getModelDefinitions();

        $count = count($modelDefs);
        $message = "Generating API routes for {$count} model(s)...";
        $progress->info($message);
        $result->addInfo($message);

        try {
            $routes = $this->generateRoutes($modelDefs);

            $outputPath = $options['routes-output'] ?? $this->routesOutputPath;
            $this->writeRoutesFile($outputPath, $routes);

            $generatedMessage = "  Generated: {$outputPath}";
            $progress->info($generatedMessage);
            $result->addInfo($generatedMessage);

            $completeMessage = "API routes generation complete!";
            $progress->info($completeMessage);
            $result->addInfo($completeMessage);

            // Set result data
            $result->setData('routes_file', $outputPath);
            $result->setData('routes_count', $count * 5); // 5 routes per model

            return $result;
        } catch (\Exception $e) {
            $errorMessage = "API routes generation failed: " . $e->getMessage();
            $progress->error($errorMessage);
            return ModelActionResult::failure($errorMessage, $e);
        }
    }

    public function getDescription(): string
    {
        return 'Generate API routes';
    }

    public function shouldRun(ModelDefinitionChangeSet $changes): bool
    {
        // Only run if models were added or modified (not removed)
        return !empty($changes->getAdded()) || !empty($changes->getModified());
    }

    private function generateRoutes(array $modelDefs): string
    {
        $routes = "<?php\n\n";
        $routes .= "/**\n";
        $routes .= " * THIS FILE IS GENERATED - DO NOT MODIFY\n";
        $routes .= " * Generated on: " . date('Y-m-d H:i:s') . "\n";
        $routes .= " */\n\n";
        $routes .= "// API Routes\n\n";

        foreach ($modelDefs as $modelDef) {
            $modelName = $modelDef->getName();
            $pluralName = $modelDef->getPluralName();
            $controllerName = $this->modelNameToClassName($modelName) . 'Controller';

            $routes .= "// {$modelDef->getFriendlyName()} routes\n";
            $routes .= "\$router->get('/api/{$pluralName}', [{$controllerName}::class, 'index']);\n";
            $routes .= "\$router->get('/api/{$pluralName}/{id}', [{$controllerName}::class, 'show']);\n";
            $routes .= "\$router->post('/api/{$pluralName}', [{$controllerName}::class, 'store']);\n";
            $routes .= "\$router->put('/api/{$pluralName}/{id}', [{$controllerName}::class, 'update']);\n";
            $routes .= "\$router->delete('/api/{$pluralName}/{id}', [{$controllerName}::class, 'destroy']);\n";
            $routes .= "\n";
        }

        return $routes;
    }

    private function writeRoutesFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function modelNameToClassName(string $modelName): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $modelName)));
    }
}