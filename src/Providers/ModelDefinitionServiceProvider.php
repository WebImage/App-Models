<?php

namespace WebImage\Models\Providers;

use WebImage\Config\Config;
use WebImage\Container\ServiceProvider\AbstractServiceProvider;

class ModelDefinitionServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        ModelDefinitionProviderInterface::class,
    ];

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(ModelDefinitionProviderInterface::class, function() {

            $config = $this->getApplicationConfig()->get('webimage/models', new Config());

            $compiledPath = $this->getCompiledPath();

            if (file_exists($compiledPath)) {
                return new CompiledModelDefinitionProvider($compiledPath);
            }

            // Fall back to file provider (development, before first sync)
            $modelFiles = $config->get('models');
            if ($modelFiles === null) {
                throw new \RuntimeException(
                    'Config at webimage/models.models must contain an array of model files. ' .
                    'Run models:sync to compile your model definitions.'
                );
            } else if (!is_array($modelFiles)) {
                throw new \RuntimeException('Config at webimage/models.models must be an array');
            }

            $variables = $config->get('variables');

            return new FileModelDefinitionProvider($modelFiles, $variables);
        });
    }

    private function getCompiledPath(): string
    {
        $compiledPath = $this->getApplicationConfig()->get('webimage/models', new Config())->get('compiledPath');


        // If the path is not absolute, then prefix it with the project path
        if (substr($compiledPath, -1) !== '/') {
            $compiledPath = $this->getAPplication()->getProjectPath() . '/' . $compiledPath;
        }

        return $compiledPath;
    }
}