<?php

namespace WebImage\Models\Helpers;

use WebImage\Core\ArrayHelper;
use WebImage\Core\VarDumper;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\MultiValuePropertyInterface;
use WebImage\Models\Properties\Property;
use WebImage\Models\Properties\PropertyInterface;
use WebImage\Models\Properties\SingleValuePropertyInterface;
use WebImage\Models\Service\RepositoryInterface;

class EntityDebugger
{
	public static function dump(Entity $entity): Entity
	{
		$debugEntity = clone $entity;

		$reflectionClass = new \ReflectionClass($debugEntity);
		$properties = $reflectionClass->getProperties();

		foreach($properties as $property) {
			$property->setAccessible(true);
			$value = $property->getValue($debugEntity);
			if ($value instanceof RepositoryInterface) $property->setValue($debugEntity, null);
		}

		return $debugEntity;
	}

	public static function toText(EntityStub $entity): string
	{
		return VarDumper::toText($entity);
	}

	public static function summarize(EntityStub $entity): array
	{
		return [
			'model' => $entity->getModel(),
			'hasRepository' => $entity instanceof Entity ? $entity->getRepository() !== null : false,
			'properties' => self::summarizeProperties($entity)
		];
	}

	private static function summarizeProperties(EntityStub $entity)
	{
		$properties = [];

		foreach($entity->getProperties() as $name => $property) {
			$propDef           = $property->getDef();
			$properties[$name] = [
				'dataType' => $propDef->getDataType(),
				'value' => self::summarizePropertyValue($property)
			];
		}

		return $properties;
	}

	private static function summarizePropertyValue(PropertyInterface $property)
	{
		if ($property->getDef()->isMultiValued()) return self::summarizeMultiValuedProperty($property);

		return self::summarizeSingleValueProperty($property);
	}

	private static function summarizeMultiValuedProperty(MultiValuePropertyInterface $property)
	{
		return '(multi-valued: ' . number_format(count($property->getValues())) . ')';
	}

	private static function summarizeSingleValueProperty(SingleValuePropertyInterface $property)
	{
		$value = $property->getValue();

		if ($value === null) return '(null)';
		else if (is_string($value) || is_int($value) || is_double($value)) return sprintf('(%s) %s', gettype($value), $value);
		else if (is_bool($value)) return '(boolean)' . ($value === true ? 'true':'false');
		else if (is_array($value)) {
			if (ArrayHelper::isAssociative($value)) {
				return '(array) ' . implode(', ', array_keys($value));
			} else {
				return '(array)';
			}
		}

		return '(unknown)';
	}
}
