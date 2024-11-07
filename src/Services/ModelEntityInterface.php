<?php

namespace WebImage\Models\Services;


use WebImage\Models\Entities\EntityStub;

/**
 * @template T
 */
interface ModelEntityInterface
{
	/**
	 * ModelEntity is a convenience wrapper/adapter around Entity that ties the entity to a specific model
	 * @return EntityStub
	 */
	public function getEntity(): EntityStub;
}