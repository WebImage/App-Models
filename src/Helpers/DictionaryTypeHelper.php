<?php

namespace WebImage\Models\Helpers;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Models\Compiler\YamlModelDefinitionHydrator;
use WebImage\Models\Defs\ModelDefinition;

class DictionaryTypeHelper
{
	/**
	 * Loads types from file (string) or array
	 * @param string|array|Config $types
	 * @param Dictionary|null $dynamicValues
	 * @return array
	 */
	public static function load($types, Dictionary $dynamicValues = null): array
	{
		return self::loadByType($types, $dynamicValues);
	}

	private static function loadByType($types, Dictionary $dynamicValues = null): array
	{
		if (is_string($types)) return self::loadFromFile($types, $dynamicValues);
		else if (is_iterable($types)) return self::loadFromArray($types, $dynamicValues);

		throw new \InvalidArgumentException(sprintf('Unsupported type format: %s', gettype($types)));
	}

	/**
	 * @param string $typesFile
	 * @param Dictionary|null $dynamicValues
	 * @return ModelDefinition[]
	 */
	private static function loadFromFile(string $typesFile, Dictionary $dynamicValues = null): array
	{
		$parser = new YamlModelDefinitionHydrator();
		$models = [];

		foreach(glob($typesFile) as $file) {
			try {
				$parsedModels = $parser->compileFile($file);
			} catch (\Exception $e) {
				throw new \RuntimeException(sprintf('Error parsing file %s: %s', $file, $e->getMessage()));
			}
			foreach($parsedModels as $model) {
				static::injectVariables($model, $dynamicValues);
				$models[] = $model;
			}
		}

		return $models;
	}

	/**
	 * @param iterable $loadTypes
	 * @param Dictionary|null $dynamicValues
	 * @return array
	 */
	private static function loadFromArray(iterable $loadTypes, Dictionary $dynamicValues = null): array
	{
		$types = [];

		foreach($loadTypes as $type) {
			$types = array_merge($types, self::loadByType($type, $dynamicValues));
		}

		return $types;
	}

	/**
	 * Updated model values that might be dynamically set using $varName - which will typically be defined in "webimage/models.variables" config.
	 * @param ModelDefinition $model
	 * @param Dictionary|null $dynamicValues
	 * @return void
	 */
	private static function injectVariables(ModelDefinition $model, Dictionary $dynamicValues = null): void
	{
		// Currently, this is only supported on the dataType value of each property
		foreach($model->getProperties() as $property) {
			$property->setDataType(self::injectVariableValues($property->getDataType(), $dynamicValues));
			$dataType = $property->getDataType();
			if ($property->getReference() !== null) {
				$property->getReference()->setTargetModel(self::injectVariableValues($property->getReference()->getTargetModel(), $dynamicValues));
			}
		}
	}

	/**
	 * Replace $varName with the value from the dynamicValues dictionary
	 * @param string $value
	 * @param Dictionary|null $dynamicValues
	 * @return array|string|string[]
	 */
	private static function injectVariableValues(string $value, Dictionary $dynamicValues = null)
	{
		if (preg_match_all('/\$[a-zA-Z_][a-zA-Z_0-9]*/', $value, $matches)) {
			foreach($matches[0] as $match) {
				$varName = substr($match, 1);
				if ($dynamicValues !== NULL && isset($dynamicValues[$varName])) {
					$value = str_replace($match, $dynamicValues[$varName], $value);
				}
			}
		}
		return $value;
	}
}
