<?php

namespace WebImage\Models\Helpers;

use WebImage\Config\Config;
use WebImage\Models\Compiler\YamlModelCompiler;
use WebImage\Models\Defs\ModelDefinition;

class DictionaryTypeHelper
{
	/**
	 * Loads types from file (string) or array
	 * @param string|array|Config $types
	 * @return array
	 */
	public static function load($types): array
	{
		$types = self::loadByType($types);

		return $types;
	}

	private static function loadByType($types): array
	{
		if (is_string($types)) return self::loadFromFile($types);
		else if (is_iterable($types)) return self::loadFromArray($types);

		throw new \InvalidArgumentException(sprintf('Unsupported type format: %s', gettype($types)));
	}

	/**
	 * @param string $typesFile
	 * @return ModelDefinition[]
	 */
	private static function loadFromFile(string $typesFile): array
	{
		$parser = new YamlModelCompiler();
		$models = [];

		foreach(glob($typesFile) as $file) {
			foreach($parser->compileFile($file) as $model) {
				$models[] = $model;
			}
		}

		return $models;
	}

	/**
	 * @param iterable $loadTypes
	 * @return array
	 */
	private static function loadFromArray(iterable $loadTypes): array
	{
		$types = [];

		foreach($loadTypes as $type) {
			$types = array_merge($types, self::loadByType($type));
		}

		return $types;
	}
}
