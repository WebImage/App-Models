<?php

namespace WebImage\Models;

use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Properties\ValueMapper\ValueMapResolverServiceProvider;
use WebImage\Models\Service\Db\RepositoryServiceProvider;
use WebImage\Models\Compiler\YamlModelCompiler;
use WebImage\Application\AbstractPlugin;
use WebImage\Application\ApplicationInterface;
use WebImage\Application\HttpApplication;

class Plugin extends AbstractPlugin {
	const CONFIG_MODELS_KEY = 'models';
	/** @var ModelDefinition[] null */
	private $models = [];

	public function load(ApplicationInterface $app)
	{
		parent::load($app);
		$this->loadServiceProviders($app);
		$this->loadModels($app);
	}

	/**
	 * Add service providers to ServiceManager
	 */
	private function loadServiceProviders(ApplicationInterface $app)
	{
		$sm = $app->getServiceManager();
		$sm->addServiceProvider(new RepositoryServiceProvider);
		$sm->addServiceProvider(new ValueMapResolverServiceProvider);
	}

	private function loadModels(ApplicationInterface $app)
	{
		$config = $app->getConfig()->get($this->getManifest()->getId());
		if ($config === null) throw new \RuntimeException('Missing config for ' . $this->getManifest()->getId());

		$modelFiles = $config->get(self::CONFIG_MODELS_KEY);
		if ($modelFiles === null) throw new \RuntimeException(sprintf('Plugin %s is missing config[%s][%s]', $this->getManifest()->getName(), $this->getManifest()->getId(), self::CONFIG_MODELS_KEY));

		if (is_string($modelFiles)) $modelFiles = [$modelFiles];
		else if (!is_array($modelFiles)) throw new \RuntimeException(sprintf('Plugin %s expects config[%s][%s] to return an array of model files.', $this->getManifest()->getName(), $this->getManifest()->getId(), self::CONFIG_MODELS_KEY));

		$importer = new YamlModelCompiler();

		foreach($modelFiles as $modelFile) {
			$this->models = array_merge($this->models, $importer->compileFile($modelFile));
		}
	}

	/**
	 * @return ModelDefinition[]
	 */
	public function getModels(): array
	{
		return $this->models;
	}
}
