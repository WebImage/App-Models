<?php

namespace WebImage\Models\Helpers;

use WebImage\Config\Config;
use WebImage\Models\Compiler\YamlModelCompiler;

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

	private static function loadByType($types) {
		if (is_string($types)) return self::loadFromFile($types);
		else if (is_iterable($types)) return self::loadFromArray($types);

		throw new \InvalidArgumentException(sprintf('Unsupported type format: %s', gettype($types)));
	}

	/**
	 * @param string $typeFile
	 * @return array|\WebImage\Models\Defs\ModelDefinition[]
	 */
	private static function loadFromFile(string $typesFile)
	{
		$parser = new YamlModelCompiler();

		return $parser->compileFile($typesFile);
	}

	/**
	 * @param iterable $types
	 */
	private static function loadFromArray(iterable $loadTypes)
	{
		$types = [];

		foreach($loadTypes as $type) {
			$types = array_merge($types, self::loadByType($type));
		}

		return $types;
	}
}
