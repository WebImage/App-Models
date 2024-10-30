<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\ModelServiceInterface;
use WebImage\Node\Service\Db\PropertyTableColumns;


class TableHelper
{
	public static function getAssociationTable(ModelServiceInterface $modelService, string $sourceType, string $targetType, ?string $sourceProperty=null, ?string $targetProperty=null): AssociationTable
	{
		$tableName     = TableNameHelper::getAssociationTableName($modelService, $sourceType, $targetType, $sourceProperty, $targetProperty);

		$sourceTypeDef         = $modelService->getModel($sourceType)->getDef();
		$sourceTableName       = TableNameHelper::getTableNameFromDef($sourceTypeDef);
		$sourcePropertyColumns = $sourceProperty === null ? self::getPrimaryKeyColumns($modelService, $sourceTypeDef) : self::getPropertiesColumns($modelService, $sourceTypeDef, $sourceProperty);

		$targetTypeDef         = $modelService->getModel($targetType)->getDef();
		$targetTableName       = TableNameHelper::getTableNameFromDef($targetTypeDef);
		$targetPropertyColumns = $targetProperty === null ? self::getPrimaryKeyColumns($modelService, $targetTypeDef) : self::getPropertiesColumns($modelService, $targetTypeDef, $targetProperty);

		$source = new AssociationTableTarget($sourceTableName, $sourceType, $sourceProperty, $sourcePropertyColumns);
		$target = new AssociationTableTarget($targetTableName, $targetType, $targetProperty, $targetPropertyColumns);

		return new AssociationTable($tableName, $source, $target);
	}

	public static function getPropertiesColumns(ModelServiceInterface $modelService, ModelDefinitionInterface $modelDef, ?string $property=null): PropertiesColumns
	{
		if ($property === null) return self::getModelColumns($modelService, $modelDef);

		$propDef           = $modelDef->getProperty($property);
		$propertiesColumns = new PropertiesColumns();
		$tableName         = TableNameHelper::getTableNameFromDef($modelDef);

		if ($propDef === null) {
			throw new \RuntimeException('Unknown property ' . $modelDef->getName() . '.' . $property);
		}

		if ($propDef->isVirtual()) {
			$reference     = $propDef->getReference();

			if ($reference === null) {
				throw new \RuntimeException('Unsupported virtual type without reference: ' . $propDef->getModel() . '.' . $propDef->getName());
			}

			$targetModel = $modelService->getModel($reference->getTargetModel());
			if ($targetModel === null) throw new \Exception(sprintf('%s.%s references an invalid type: %s', $propDef->getModel(), $propDef->getName(), $reference->getTargetModel()));
			$targetModelDef = $targetModel->getDef();

			foreach(self::getPrimaryKeyColumns($modelService, $targetModelDef)->getProperties() as $key => $modelTableColumns) {
				$localModelColumns = new ModelPropertyTableColumns($modelDef->getPluralName(), $tableName);
				$localModelColumns->setReferencedModel($modelTableColumns->getModel());
				$localModelColumns->setReferencedTable($modelTableColumns->getTable());
				$localModelColumns->setReferencedProperty($key);

				foreach($modelTableColumns->getColumns() as $tableColumn) {
					$localColumnName = TableNameHelper::getColumnKey($propDef->getName(), $tableColumn->getName());
					$localTableColumn = new TableColumn($tableName, $localColumnName, $tableColumn->getDataTypeField(), $tableColumn->getName());
					$localModelColumns->addColumn($localTableColumn);
				}

				$propertiesColumns->setPropertyColumns($propDef->getName(), $localModelColumns);
			}
		} else {
			$dataTypeService = $modelService->getRepository()->getDataTypeService();
			$dataType        = $dataTypeService->getDefinition($propDef->getDataType());

			if ($dataType === null) {
				throw new \RuntimeException('Unable to find dataType for ' . $modelDef->getPluralName() . '.' . $propDef->getName() . '(' . $propDef->getDataType() . ')');
			}

			$propertyColumns = new ModelPropertyTableColumns($propDef->getModel(), TableNameHelper::getTableNameFromDef($modelDef));

			foreach($dataType->getModelFields() as $modelField) {
				$subKey         = strlen($modelField->getKey()) == 0 ? '' : $modelField->getKey();
				$columnName     = TableNameHelper::getColumnKey($propDef->getName(), $subKey);
				$propertyColumn = new TableColumn($tableName, $columnName, $modelField);

				$propertyColumns->addColumn($propertyColumn);
			}

			$propertiesColumns->setPropertyColumns($property, $propertyColumns);
		}

		return $propertiesColumns;
	}

	/**
	 * @throws \Exception
	 */
	public static function getPropertyColumns(ModelServiceInterface $modelService, ModelDefinitionInterface $modelDef, string $property): ModelPropertyTableColumns
	{
		return self::getPropertiesColumns($modelService, $modelDef, $property)->getPropertyColumns($property);
	}

	/**
	 * @throws \Exception
	 */
	public static function getModelColumns(ModelServiceInterface $modelService, ModelDefinitionInterface $modelDef): PropertiesColumns
	{
		$propertiesColumns= new PropertiesColumns();

		foreach($modelDef->getProperties() as $property) {
			$columns = self::getPropertiesColumns($modelService, $modelDef, $property->getName())->getPropertyColumns($property->getName());
			$propertiesColumns->setPropertyColumns($property->getName(), $columns);
		}

		return $propertiesColumns;
	}

	/**
	 * Get the table columns for each of the primary key columns
	 * @param ModelServiceInterface $modelService
	 * @param ModelDefinitionInterface $modelDef
	 * @param string|null $property
	 * @return PropertiesColumns
	 * @throws \Exception
	 */
	public static function getPrimaryKeyColumns(ModelServiceInterface $modelService, ModelDefinitionInterface $modelDef): PropertiesColumns
	{
		$propertiesColumns = new PropertiesColumns();
		$primaryKeys       = $modelDef->getPrimaryKeys()->keys();

		foreach($primaryKeys as $primaryKey) {
			$primaryKeyColumns = self::getPropertiesColumns($modelService, $modelDef, $primaryKey)->getPropertyColumns($primaryKey);
			$propertiesColumns->setPropertyColumns($primaryKey, $primaryKeyColumns);
		}

		return $propertiesColumns;
	}
}
