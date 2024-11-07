<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Entities\EntityReference;

class DbEntityReference extends EntityReference
{
	private PropertyLoaderInterface $propertyLoader;

	public function getPropertyLoader(): PropertyLoaderInterface
	{
		return $this->propertyLoader;
	}

	public function setPropertyLoader(PropertyLoaderInterface $propertyLoader): void
	{
		$this->propertyLoader = $propertyLoader;
	}
}