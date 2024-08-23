<?php

namespace WebImage\Models;

use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Properties\ValueMapper\ValueMapResolverServiceProvider;
use WebImage\Models\Services\Db\RepositoryServiceProvider;
use WebImage\Models\Compiler\YamlModelCompiler;
use WebImage\Application\AbstractPlugin;
use WebImage\Application\ApplicationInterface;
use WebImage\Application\HttpApplication;

class Plugin extends AbstractPlugin {
	protected function load(ApplicationInterface $app): void
	{
		$this->loadServiceProviders($app);
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
}
