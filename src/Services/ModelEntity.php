<?php
/**
 * Provides a base class for concrete entity adapters
 * Entities returned from Repository are loosely typed.  This class provides a way to wrap the entity in a strongly typed class.
 */
namespace WebImage\Models\Services;

use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;

/**
 * @template T
 */
abstract class ModelEntity
{
	protected EntityStub $entity;

	public function __construct(EntityStub $entity)
	{
		$this->entity = $entity;
	}

	public function getEntity(): Entity
	{
		return $this->entity;
	}

	public function save()
	{
		if (!($this->entity instanceof Entity)) throw new \RuntimeException('Only entities can be saved');

		$this->entity->save();
	}
}
