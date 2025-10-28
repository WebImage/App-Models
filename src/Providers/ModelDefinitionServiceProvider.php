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

            $modelFiles = $config->get('models');
            if ($modelFiles === null) {
                throw new \RuntimeException('Config at webimage/models.models must contain an array of model files to include');
            } else if (!is_array($modelFiles)) {
                throw new \RuntimeException('Config at webimage/models.models must be an array');
            }

            $variables = $config->get('variables');

            return new FileModelDefinitionProvider($modelFiles, $variables);
        });
    }
}