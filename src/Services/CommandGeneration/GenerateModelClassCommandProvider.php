<?php

namespace WebImage\Models\Services\CommandGeneration;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Models\Commands\GenerateClassesCommand;
use WebImage\Models\Commands\SyncModelsCommand;

class GenerateModelClassCommandProvider extends AbstractServiceProvider
{
	protected array $provides = [
		GenerateClassesCommand::class,
        SyncModelsCommand::class,
	];

	public function register(): void
	{
		$container = $this->getContainer();
		$container->addShared(GenerateClassesCommand::class, function () use ($container) {
			return new GenerateClassesCommand(null, $this->getBaseNamespace(), $this->getOutputDir(), $this->getTemplateDir());
		});
        $container->addShared(SyncModelsCommand::class, function () use ($container) {
           return new SyncModelsCommand(null, $this->getBaseNamespace(), $this->getOutputDir(), $this->getTemplateDir(), $this->getCompiledModelsPath());
        });
	}

	private function getBaseNamespace(): string
	{
		return ltrim($this->getApplicationConfig()->get('app.namespace'), '/\\') . '\\Models';
	}

	private function getOutputDir(): string
	{
		return $this->getApplication()->getProjectPath() . '/src/Models';
	}

    private function getTemplateDir(): string
    {
        $templateDir = $this->getApplicationConfig()->get('webimage/models.templateDirectory');
        if ($templateDir === null) {
            throw new \Exception('Template directory not set at webimage/models.templateDirectory');
        }

        return $templateDir;
    }

    private function getCompiledModelsPath(): string
    {
        $compiledPath = $this->getApplicationConfig()->get('webimage/models.compiledPath');

        if (substr($compiledPath, -1) !== '/') {
            $compiledPath = $this->getApplication()->getProjectPath() . '/' . $compiledPath;
        }

        return $compiledPath;
    }
}

