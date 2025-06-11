<?php

namespace WebImage\Models\Services\CommandGeneration;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Models\Commands\GenerateModelClassesCommand;

class GenerateModelClassCommandProvider extends AbstractServiceProvider
{
	protected array $provides = [
		GenerateModelClassesCommand::class,
	];

	public function register(): void
	{
		$container = $this->getContainer();
		$container->addShared(GenerateModelClassesCommand::class, function () use ($container) {
			return new GenerateModelClassesCommand(null, $this->getBaseNamespace(), $this->getOutputDirectory());
		});
	}

	private function getBaseNamespace(): string
	{
		return ltrim($this->getApplicationConfig()->get('app.namespace'), '/\\');
	}

	private function getOutputDirectory(): string
	{
		return $this->getApplication()->getProjectPath() . '/src';
	}
}

