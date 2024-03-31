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

	public function createReference(string $modelname): EntityReference
	{
		$model = $this->getRepository()->getModelService()->getModel($modelname);

		$entity = new EntityReference($modelname);
		$this->populateDefaultPropertyValues($model, $entity);

		return $entity;
	}


	protected function populateDefaultPropertyValues(Model $model, EntityStub $entity)
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
}
