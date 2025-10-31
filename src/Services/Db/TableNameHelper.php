<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\ModelServiceInterface;
use WebImage\String\Helper;

class TableNameHelper
{
	/**
	 * Generate a table name based on the type definition
	 * @param ModelDefinitionInterface $def
	 * @param string $property To return a table name specific to a property
	 * @return string
	 */
	public static function getTableNameFromDef(ModelDefinitionInterface $def, ?string $property=null): string
	{
		#if ($def instanceof NodeTypeRefInterface && null !== $def->getTableKey() && strlen($def->getTableKey()) > 0) return $def->getTableKey(); // Table key already defined
		if ($def->getConfig()->has('modelKey')) return $def->getConfig()->get('modelKey'); // Table key defined in config as modelKey

		$tablePrefix = ''; //$def->isExtension() ? 'nx' : 'nt'; // nt = node type; nx = node extension
		$tableName = $def->getPluralName();
		/* if (empty($tableName)) */ $tableName = self::generateTableKey($def);

		$tableName = self::getDatabaseFriendlyName($tableName);
		$tableName = strlen($tablePrefix) > 0 ? $tablePrefix . '_' . $tableName : $tableName;

		if ($property !== null) {
			if (empty($property)) {
				throw new \InvalidArgumentException('$property cannot be an empty string');
			}
			return sprintf('%s_p_%s', $tableName, self::getDatabaseFriendlyName($property));
		}

		return $tableName;
	}

	public static function getPropertyTableName(ModelDefinitionInterface $def, PropertyDefinition $propDef): string
	{
		return self::getTableNameFromDef($def, $propDef->getName());
	}

//	public static function shouldDefHavePhysicalTable(ModelDefinitionInterface $def): bool
//	{
//		foreach($def->getProperties() as $propDef) {
//			if (!$propDef->isVirtual()) return true;
//		}
//
//		return false;
//	}

	/**
	 * Get the base node table to use for queries (the "FROM" table)
	 * @param ModelServiceInterface $modelService
	 * @param string $modelName
	 *
	 * @return string
	 */
	public static function getRootTableName(ModelServiceInterface $modelService, string $modelName): string
	{
		return self::getTableNameFromDef($modelService->getModel($modelName)->getDef());
	}

	/**
	 * @param string $name
	 * @param string ...$subKeys
	 * @return string
	 */
	public static function getColumnKey(string $name, ?string ...$subKeys): string
	{
		$key = self::getDatabaseFriendlyName($name);

		// Append any sub-key value
		foreach($subKeys as $subKey) {
			if (empty($subKey)) continue;
			$key .= '_' . self::getDatabaseFriendlyName($subKey);
		}

		return $key;
	}

	private static function getDatabaseFriendlyName(string $name)
	{
		$name = strtolower(Helper::camelToUnderscore($name)); // Lower case

		return preg_replace('/[^0-9a-z_]+/', '', $name);
	}

	/**
	 * Convenience method for formatting column alias
	 * @param string $tableKey
	 * @param string $column
	 * @param string $propName (complex dataTypes types will have "child" columns names, where $column becomes $column__$property)
	 *
	 * @return string
	 */
//	public static function getColumnNameAlias(string $tableKey, string $column, string $propName=null): string
	public static function getColumnNameAlias(string $tableKey, string $column, ?string ...$subKeys): string
	{
		$format = '%s__%s';

		return sprintf($format, $tableKey, TableNameHelper::getColumnKey($column, ...$subKeys));
	}

	/**
	 * Convenience method for formatting column alias
	 * @param string $propName (complex dataTypes types will have "child" columns names, where $column becomes $column__$property)
	 * @param string $targetTable
	 * @return string
	 */
	public static function getPropertyTableAlias(string $propName, string $targetTable): string
	{
		return sprintf('p_%s', $propName);
//		$format = '%s__%s';
//
//		return sprintf($format, $propName, $targetTable);
	}

	/**
	 * Convenience method for formatting column
	 * @param string $tableKey
	 * @param string $columnKey
	 * @param string|null ...$subKeys
	 * @return string
	 */
	public static function getTableColumnName(string $tableKey, string $columnKey, ?string ...$subKeys): string
	{
		return sprintf('`%s`.`%s`', $tableKey, TableNameHelper::getColumnKey($columnKey, ...$subKeys));
	}

//	/**
//	 * Get the name of a column that references another table
//	 *
//	 * @param ModelDefinitionInterface $typeDef
//	 * @param PropertyDefinition $propDef
//	 * @param TableColumn $column
//	 * @return string
//	 */
//	public static function getRefColumnName(ModelDefinitionInterface $typeDef, PropertyDefinition $propDef, TableColumn $column): string
//	{
//		return sprintf('%s_%s_%s', self::getDatabaseFriendlyName($typeDef->getPluralName()), self::getDatabaseFriendlyName($propDef->getName()), self::getDatabaseFriendlyName($column->getName()));
//	}
	public static function getRefColumnNameAlias(string $tableKey, string $propName, string $column, ?string $subKey): string
	{
		$format = '%s__%s';

		return sprintf($format, $tableKey, TableNameHelper::getColumnKey($propName, $column, $subKey));
	}

	/**
	 * Get a table name for a join operation
	 *
	 * @param ModelServiceInterface $modelService
	 * @param string $sourceModel
	 * @param string $targetModel
	 * @param string|null $sourceProperty
	 * @param string|null $targetProperty
	 * @return string
	 */
	public static function getAssociationTableName(ModelServiceInterface $modelService, string $sourceModel, string $targetModel, ?string $sourceProperty=null, ?string $targetProperty=null): string
	{
		$sourceModelDef = $modelService->getModel($sourceModel)->getDef();
		$targetModelDef = $modelService->getModel($targetModel)->getDef();

		/**
		 * If this is a source table property reference to another target
		 * table primary key (not a specific property) then name the association
		 * after the source table name + property)
		 */
		if ($sourceProperty !== null && $targetProperty === null) {
			return self::getTableNameFromDef($sourceModelDef, $sourceProperty);
		}

		// Sort tables alphabetically to ensure that duplicate table is not created with the reverse definition, e.g. typeA_typeB vs typeB_typeA
		$tables = [
			self::getTableNameFromDef($sourceModelDef, $sourceProperty),
			self::getTableNameFromDef($targetModelDef, $targetProperty)
		];

		sort($tables);

		list($sourceTable, $targetTable) = $tables;

		return sprintf('%s_%s', $sourceTable, $targetTable);
	}

	/**
	 * Generates a table name that can be used when plural name is not set
	 *
	 * @param ModelDefinitionInterface $def
	 * @return string
	 */
	private static function generateTableKey(ModelDefinitionInterface $def): string
	{
		$parts = explode('.', $def->getPluralName());

		return array_pop($parts);
	}
}
