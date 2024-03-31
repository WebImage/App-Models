<?php

namespace WebImage\Models\Helpers;

use WebImage\Core\ArrayHelper;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Exceptions\MissingModelException;
use WebImage\Models\Exceptions\InvalidPropertyException;
use WebImage\Models\Exceptions\MissingPropertyException;
use WebImage\Models\Properties\MultiValuePropertyInterface;
use WebImage\Models\Services\EntityServiceInterface;
use WebImage\Models\Services\RepositoryInterface;

class EntityArrayImportHelper
{
	/**
	 * Take an entity in data form and convert it to the right structure
	 * @param EntityServiceInterface $entityService
	 * @param string $modelName
	 * @param array $data
	 * @return mixed
	 */
	public static function createEntityFromArray(EntityServiceInterface $entityService, string $modelName, array $data): Entity
	{
		return self::populateEntityFromArray($entityService, $entityService->create($modelName), $data);
	}

	public static function populateEntityFromArray(EntityServiceInterface $entityService, EntityStub $entity, array $data): Entity
	{
		$model = $entityService->getRepository()->getModelService()->getModel($entity->getModel());

		if ($model == null) {
			throw new MissingModelException($entity->getModel());
		}

		self::populateProperties($entity, $model->getDef(), $data);

		return $entity;
	}

	/**
	 * Extract property values from data and add to entity
	 *
	 * @param ModelDefinitionInterface $modelDef
	 * @param array $data
	 */
	private static function populateProperties(EntityStub $entity, ModelDefinitionInterface $modelDef, array $data)
	{
		$allowedModelProperties = self::getAllowedModelProperties($modelDef);
		$propertyKeys           = array_keys($data);
		self::assertValidProperties($allowedModelProperties, $propertyKeys);

		foreach($modelDef->getProperties() as $propDef) {
			$exists = in_array($propDef->getName(), $propertyKeys);

			if (!$exists) {
				// Make sure that required properties are specified
				if ($propDef->isRequired()) {
					throw new MissingPropertyException($modelDef->getPluralName() . '.' . $propDef->getName() . ' is required');
				}
				continue;
			}

			self::populateProperty($entity, $propDef, $data[$propDef->getName()]);
		}
	}

	/**
	 * Populate property value
	 *
	 * @param Entity $entity
	 * @param PropertyDefinition $propDef
	 * @param $value
	 */
	private static function populateProperty(EntityStub $entity, PropertyDefinition $propDef, /* mixed */ $value)
	{
		if ($propDef->isVirtual()) {
			if ($propDef->getReference() === null) return;

			self::populateVirtualProperty($entity, $propDef, $value);
		} else {
			$property = $entity->getProperty($propDef->getName());
			$property->setValue($value);
		}
	}

	/**
	 * Populate a virtual property's value
	 *
	 * @param Entity $entity
	 * @param PropertyDefinition $propDef
	 * @param $value
	 */
	private static function populateVirtualProperty(EntityStub $entity, PropertyDefinition $propDef, /* mixed */ $value)
	{
		if ($propDef->isMultiValued()) {
			echo 'Multi-valued: ' . $propDef->getModel() . '.' . $propDef->getName() . PHP_EOL;
		} else {
			if (!is_array($value) || !ArrayHelper::isAssociative($value)) {
				throw new InvalidPropertyException($propDef->getModel() . '.' . $propDef->getName() . ' must be specified as an object, e.g. {"key": "value")');
			}

			$virtual = $entity->getRepository()->getEntityService()->create($propDef->getModel());

			#$virtual = new EntityStub($propDef->getModel());
			#self::populateEntityFromArray($entity->getRepository()->getEntityService(), $virtual, $value);
			echo '<pre>';
			print_r(EntityDebugger::summarize($virtual));
			die(__FILE__ . ':' . __LINE__ . PHP_EOL);
			#die(__FILE__ . ':' . __LINE__ . PHP_EOL);
			$refModel  = self::getModelDefFromName($entity->getRepository(), $propDef->getModel());
			$allowedProperties = self::getAllowedModelProperties($refModel);
			$propertyKeys = array_keys($value);
			self::assertValidProperties($allowedProperties, $propertyKeys);

			$entity->getProperty($propDef->getName())->setValue($value);
		}
	}

	/**
	 * Get a list of valid property names for a model
	 *
	 * @param ModelDefinitionInterface $modelDef
	 * @return array
	 */
	private static function getAllowedModelProperties(ModelDefinitionInterface $modelDef)
	{
		$allowed = [];

		foreach($modelDef->getProperties() as $property) {
			$allowed[] = $property->getName();
		}

		return $allowed;
	}

	/**
	 * Ensure that the properties specified are actually properties for the model
	 *
	 * @param array $allowedKeys A list of allowed keys
	 * @param array $keys A list of keys specified
	 */
	private static function assertValidProperties(array $allowedKeys, array $keys)
	{
		$diffs = array_diff($keys, $allowedKeys);

		if (count($diffs)) {
			throw new InvalidPropertyException('Invalid properties specified for entity: ' . implode(', ', $diffs));
		}
	}

	private static function getModelDefFromName(RepositoryInterface $repository, string $modelName): ModelDefinitionInterface
	{
		$model = $repository->getModelService()->getModel($modelName);

		if ($model === null) {

		}

		return $model->getDef();
	}
}
