<?php

namespace WebImage\Models\Helpers;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Models\Properties\YamlPropertyTypeImporter;

class DictionaryPropertyTypeHelper
{
	/**
	 * Loads models from file (string) or array
	 * @param string|array|Config $types
	 * @return array
	 */
	public static function load($types): array
	{
		return self::loadByType($types);
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
		$parser = new YamlPropertyTypeImporter();

		return $parser->importFile($typesFile);
	}

	/**
	 * @param iterable $types
	 */
	private static function loadFromArray(iterable $types)
	{
		$parser = new YamlPropertyTypeImporter();
		if ($types instanceof Dictionary) $types = $types->toArray();

		return $parser->importArray($types);
	}
}
