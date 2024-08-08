<?php

namespace WebImage\Models\Properties\ValueMapper;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;

class ValueMapResolverServiceProvider extends AbstractServiceProvider
{
	protected array $provides = [
		ValueMapResolver::class
	];

	public function register(): void
	{
		$this->registerDataValueMappers();
	}

	private function registerDataValueMappers() {
		$config = $this->getApplicationConfig();
		$mappers = $config->get('webimage/models.dataValueMappers', []);

		$mapper = new ValueMapResolver();
		foreach($mappers as $key => $class) {
			$mapper->register($class, $key);
		}

		$this->getContainer()->addShared(ValueMapResolver::class, $mapper);
	}
}
