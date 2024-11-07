<?php

namespace WebImage\Models\Services;

use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Compiler\InvalidModelException;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\Model;
use WebImage\Models\Properties\AbstractProperty;
use WebImage\Models\Properties\MultiValueProperty;
use WebImage\Models\Properties\Property;
use WebImage\Models\Query\QueryBuilder;

abstract class AbstractEntityService implements EntityServiceInterface
{
	use RepositoryAwareTrait;

	/**
	 * @inheritdoc
	 */
	public function create(string $modelName): Entity
	{
		$model = $this->getRepository()->getModelService()->getModel($modelName);

		if ($model === null) {
			throw new InvalidModelException('Unknown type: ' . $modelName);
		}

		$entity = new Entity($modelName, $this->getRepository());

		$this->populateDefaultPropertyValues($model, $entity);

		return $entity;
	}

	/**
	 * Create entity and populate default property values
	 * @param string $modelName
	 * @return EntityReference
	 */
	public function createReference(string $modelName): EntityReference
	{
		$model = $this->getRepository()->getModelService()->getModel($modelName);

		$entity = $this->createEntityReference($modelName);
		$this->populateDefaultPropertyValues($model, $entity);

		return $entity;
	}

	/**
	 * Create the actual EntityReference object
	 * @param string $modelName
	 * @return EntityReference
	 */
	protected function createEntityReference(string $modelName): EntityReference
	{
		return new EntityReference($modelName);
	}


	protected function populateDefaultPropertyValues(Model $model, EntityStub $entity): EntityStub
	{
		foreach($model->getDef()->getProperties() as $propertyDef) {

			if ($propertyDef->isMultiValued()) {
				$property = new MultiValueProperty();
				$property->setDef($propertyDef);
				if ($propertyDef->getDefault() !== null) {
					$property->setValues([$propertyDef->getDefault()]);
				}
			} else {
				$property = new Property();
				$property->setDef($propertyDef);
				$property->setValue($propertyDef->getDefault());
			}

			$entity->addProperty($propertyDef->getName(), $property);
		}

		return $entity;
	}

	public function createQueryBuilder(): QueryBuilder
	{
		return new QueryBuilder($this);
	}

//	/**
//	 * Mark all properties as having been loaded
//	 * @param Entity $entity
//	 * @return void
//	 */
//	private function markPropertiesAsLoaded(EntityStub $entity)
//	{
//		foreach($entity->getProperties() as $property) {
//			$property->setIsValueLoaded(true);
//		}
//	}
}
