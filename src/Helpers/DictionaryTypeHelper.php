<?php

namespace WebImage\Models\Helpers;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Models\Compiler\YamlModelCompiler;
use WebImage\Models\Defs\ModelDefinition;

class DictionaryTypeHelper
{
	/**
	 * Loads types from file (string) or array
	 * @param string|array|Config $types
	 * @return array
	 */
	public static function load($types, Dictionary $dynamicValues): array
	{
		return self::loadByType($types, $dynamicValues);
	}

	private static function loadByType($types, Dictionary $dynamicValues): array
	{
		if (is_string($types)) return self::loadFromFile($types, $dynamicValues);
		else if (is_iterable($types)) return self::loadFromArray($types, $dynamicValues);

		throw new \InvalidArgumentException(sprintf('Unsupported type format: %s', gettype($types)));
	}

	/**
	 * @param string $typesFile
	 * @param Dictionary $dynamicValues
	 * @return ModelDefinition[]
	 */
	private static function loadFromFile(string $typesFile, Dictionary $dynamicValues): array
	{
		$parser = new YamlModelCompiler();
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
	 * @param Dictionary $dynamicValues
	 * @return array
	 */
	private static function loadFromArray(iterable $loadTypes, Dictionary $dynamicValues): array
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
	 * @param Dictionary $dynamicValues
	 * @return void
	 */
	private static function injectVariables(ModelDefinition $model, Dictionary $dynamicValues): void
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
	 * @param Dictionary $dynamicValues
	 * @return array|string|string[]
	 */
	private static function injectVariableValues(string $value, Dictionary $dynamicValues)
	{
		if (preg_match_all('/\$[a-zA-Z_][a-zA-Z_0-9]*/', $value, $matches)) {
			foreach($matches[0] as $match) {
				$varName = substr($match, 1);
				if (isset($dynamicValues[$varName])) {
					$value = str_replace($match, $dynamicValues[$varName], $value);
				}
			}
		}
		return $value;
	}
}
