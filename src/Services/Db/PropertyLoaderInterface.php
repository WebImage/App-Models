<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Entities\EntityStub;

interface PropertyLoaderInterface
{
	/**
	 * Lazily loads a property value for an entity
	 * @param string $property
	 * @param EntityStub[] $entities
	 * @return void
	 */
	public function loadPropertyForEntities(string $property, array $entities): void;
}
