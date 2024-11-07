<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Entities\EntityStub;

interface PropertyLoaderInterface
{
	/**
	 * Lazily loads a property value for a set of entities
	 * @param string $propertyName
	 * @param EntityStub[] $entities
	 * @return void
	 */
	public function loadPropertyForEntities(string $propertyName, array $entities): void;
}
