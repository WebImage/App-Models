<?php

namespace WebImage\Models\Properties;

use Symfony\Component\Yaml\Yaml;
use WebImage\Config\Config;
use WebImage\Core\ArrayHelper;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\DataTypeField;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Security\RoleAccessInterface;

class YamlPropertyTypeImporter
{
	/**
	 * Import an associative array [type-name => [definition...]]
	 * @param array $arr
	 * @return array
	 */
	public function importArray(array $arr): array
	{
		if (!ArrayHelper::isAssociative($arr)) throw new \RuntimeException('Only [type => def] type definitions are accepted at this time');

		$types = [];

		foreach($arr as $propName => $def) {
			$types[] = self::importPropertyType($this->normalizeDef($propName, $def));
		}

		return $types;
	}

	/**
	 * Normalized received definition
	 * @param string $propName
	 * @param array|string|mixed $def
	 * @return array|mixed
	 */
	private function normalizeDef(string $propName, $def)
	{
		if ($def === null) $def = ['name' => $propName, 'friendly' => $propName, 'fields' => []];

		if (!array_key_exists('name', $def)) $def['name'] = $propName;

		return $def;
	}

	private function importPropertyType(array $struct): DataTypeDefinition
	{
		ArrayHelper::assertKeys($struct, 'type', ['name', 'friendly'], ['field', 'fields', 'mapper', 'view']);

		$propTypeDef = new DataTypeDefinition($struct['name'], $struct['friendly']);
		$typeFields = $this->importTypeFields($propTypeDef, $struct);

		if (array_key_exists('mapper', $struct)) $propTypeDef->setValueMapper($struct['mapper']);
		if (array_key_exists('view', $struct)) $propTypeDef->setView($struct['view']);

		foreach($typeFields as $typeField) {
			$propTypeDef->addTypeField($typeField);
		}

		return $propTypeDef;
	}

	private function importTypeFields(DataTypeDefinition $propTypeDef, array $struct): array
	{
		$typeFields = $this->normalizeTypeFields($propTypeDef, $struct);

		return array_map(function(array $typeField) {
			$config = new Config($typeField);

			return DataTypeField::createFromConfig($config);
		}, $typeFields);
	}

	private function normalizeTypeFields(DataTypeDefinition $propTypeDef, array $struct): array
	{
		$useTypeField = array_key_exists('field', $struct);
		$useTypeFields = array_key_exists('fields', $struct);

		if ( ($useTypeField && $useTypeFields) || (!$useTypeField && !$useTypeFields) ) {
			throw new \RuntimeException(sprintf('%s must have field or fields defined', $propTypeDef->getName()));
		}

		$typeFields = $useTypeFields ? $struct['fields'] : [$struct['field']];

		return array_map(function($typeField) {
			if (is_string($typeField)) $typeField = ['type' => $typeField];
			else if ($typeField instanceof Config) $typeField = $typeField->toArray();

			return $typeField;
		}, $typeFields);
	}
}
