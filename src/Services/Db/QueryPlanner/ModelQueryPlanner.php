<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use Exception;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\DataTypeField;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Query\Property;
use WebImage\Models\Query\Query;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\Db\TableHelper;
use WebImage\Models\Services\Db\TableNameHelper;
use WebImage\Models\Services\ModelServiceInterface;
use function Symfony\Component\String\b;

class ModelQueryPlanner
{
	private ModelServiceInterface $modelService;

	/**
	 * @param ModelServiceInterface $modelService
	 */
	public function __construct(ModelServiceInterface $modelService)
	{
		$this->modelService = $modelService;
	}

	/**
	 * Create a table plan for retrieving the data for a given model
	 * @param string $model
	 * @param Query $query
	 * @return TablePlan
	 */
	public function planModelQuery(string $model, Query $query): TablePlan
	{
		$modelDef   = $this->getModelService()->getModel($model)->getDef();
		$modelTable = TableNameHelper::getTableNameFromDef($modelDef);
		$tablePlan  = new TablePlan($model, $modelTable, $modelTable);
		$this->planProperties($query, $tablePlan, $modelDef);

		return $tablePlan;
	}

	/**
	 * Plan the properties for a model (branching into multi- and single-value properties)
	 * @param Query $query
	 * @param TablePlan $tablePlan
	 * @param ModelDefinitionInterface $modelDef
	 * @return void
	 */
	private function planProperties(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef)
	{
		$this->planMultiValueProperties($query, $tablePlan, $modelDef);
		$this->planSingleValueProperties($query, $tablePlan, $modelDef);
	}

	/**
	 * Plan the multi-valued properties for a model
	 * @param Query $query
	 * @param TablePlan $tablePlan
	 * @param ModelDefinitionInterface $modelDef
	 * @return void
	 */
	private function planMultiValueProperties(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef)
	{
		foreach ($modelDef->getProperties() as $propDef) {
			if (!$propDef->isMultiValued()) continue;
			$this->planMultiValueProperty($query, $tablePlan, $modelDef, $propDef);
		}
	}

	/**
	 *
	 * @param Query $query
	 * @param TablePlan $tablePlan
	 * @param ModelDefinitionInterface $modelDef
	 * @return void
	 */
	private function planSingleValueProperties(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef)
	{
		foreach ($modelDef->getProperties() as $propDef) {
			if ($propDef->isMultiValued()) continue;
			$this->planSingleValueProperty($query, $tablePlan, $modelDef, $propDef);
		}
	}

	/**
	 * Plan a single-valued property
	 * @param Query $query
	 * @param TablePlan $tablePlan
	 * @param ModelDefinitionInterface $modelDef
	 * @param PropertyDefinition $propDef
	 * @return void
	 */
	private function planSingleValueProperty(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		if ($propDef->isVirtual()) $this->planSingleValueVirtualProperty($query, $tablePlan, $modelDef, $propDef);
		else $this->planSingleInlineValueProperty($query, $tablePlan, $propDef);
	}

	/**
	 * Plan for properties that reference other models.  A referenced property can be lazily or eagerly loaded.  Branch here to handle both cases.
	 *
	 * @param Query $query
	 * @param TablePlan $tablePlan
	 * @param ModelDefinitionInterface $modelDef
	 * @param PropertyDefinition $propDef
	 * @return void
	 * @throws Exception
	 */
	private function planSingleValueVirtualProperty(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		if (!$propDef->hasReference()) return;

		$refModelDef        = $this->getModelService()->getModel($propDef->getReference()->getTargetModel())->getDef();
		$refModelTable      = TableNameHelper::getTableNameFromDef($refModelDef);
		$refModelTableAlias = TableNameHelper::getPropertyTableAlias($propDef->getName(), $refModelTable);

		// Setup JOIN table
		$this->createReferenceTableJoinPlan($tablePlan, $propDef, $refModelDef, $refModelTable, $refModelTableAlias);

//		if ($query->isJoinedProperty($propDef->getModel(), $propDef->getName())) $this->createEagerReferenceTablePlan($tablePlan, $propDef, $refModelDef, $refModelTable, $refModelTableAlias);
//		else $this->createLazyReferenceTablePlan($tablePlan, $propDef, $refModelDef, $refModelTable, $refModelTableAlias);
		$this->createLazyReferenceTablePlan($tablePlan, $propDef, $refModelDef, $refModelTable, $refModelTableAlias);
	}

//	private function createEagerReferenceTablePlan(TablePlan $tablePlan, PropertyDefinition $propDef, ModelDefinition $refModelDef, string $refModelTable, string $refModelTableAlias)
//	{
//		$columns = [];
//		foreach ($refModelDef->getProperties() as $refPropDef) {
//			$propRefColumns = $this->getColumnsForReferencedModelPropertyColumnPlan($refPropDef, $refModelTableAlias);
//			echo '<pre>';print_r($propRefColumns); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//			$columns[]      = new ReferencePropertyPlan($propDef->getModel(), $propDef->getName(), $refPropDef->getName(), $propRefColumns);
//		}
//
//		$referencedProperty = new ReferencedModelPlan($propDef->getModel(), $propDef->getName(), $refModelDef->getName(), $columns);
//
//		$tablePlan->addPropertyPlan($propDef->getName(), $referencedProperty);
//	}

	/**
	 * @throws Exception
	 */
	private function createLazyReferenceTablePlan(TablePlan $tablePlan, PropertyDefinition $propDef, ModelDefinition $refModelDef, string $refModelTable, string $refModelTableAlias)
	{
		// Add JOIN criteria
		$columns = [];
		foreach ($refModelDef->getPrimaryKeys() as $refPropDef) {
			$propRefColumns = $this->getColumnsForReferencePropertyPlan($propDef, $refPropDef, $tablePlan->getTableAlias());
			$columns[]      = new ReferencePropertyPlan($propDef->getModel(), $propDef->getName(), $refPropDef->getName(), $propRefColumns);
		}

		$referencedProperty = new ReferencedModelPlan($propDef->getModel(), $propDef->getName(), $refModelDef->getName(), $columns);

		$tablePlan->addPropertyPlan($propDef->getName(), $referencedProperty);
	}

	private function createReferenceTableJoinPlan(TablePlan $tablePlan, PropertyDefinition $propDef, ModelDefinition $refModelDef, string $refModelTable, string $refModelTableAlias)
	{
		$referencePropertyTable = new ReferenceTablePlan($refModelDef->getName(), $refModelTable, $refModelTableAlias, $tablePlan->getTableAlias());

		// Add referenced model columns
		foreach($refModelDef->getProperties() as $refPropDef) {
			$refModelColumns = $this->getColumnsForReferencedModelPropertyColumnPlan($refPropDef, $refModelTableAlias);
			$referencePropertyTable->addPropertyPlan($refPropDef->getName(), new PropertyPlan($refPropDef->getModel(), $refPropDef->getName(), $refModelColumns));

			if ($refPropDef->isPrimaryKey()) {
				$sourceColumn = TableNameHelper::getColumnKey($refPropDef->getName());
				$targetColumn = TableNameHelper::getColumnKey($propDef->getName(), $refPropDef->getName());
				$referencePropertyTable->addJoinCriteria(new JoinReferenceTableCriteria($refModelTableAlias, $sourceColumn, $tablePlan->getTableAlias(), $targetColumn));
				$referencePropertyTable->addWhere(new WhereMatchKey($tablePlan->getTableAlias(), $sourceColumn, $propDef->getName(), $refPropDef->getName()));
			}
		}

		$tablePlan->addReferenceProperty($propDef->getName(), $referencePropertyTable);
	}

	/**
	 * Create the columns required for the referenced model's properties
	 * @param PropertyDefinition $propDef
	 * @param string $tableName
	 * @return ReferenceColumn[]
	 * @throws Exception
	 */
	private function getColumnsForReferencedModelPropertyColumnPlan(PropertyDefinition $propDef, string $tableName): array
	{
		$dataType = $this->getDataTypeService()->getDefinition($propDef->getDataType());
		$columns = [];

		if ($dataType->isSimpleStorage()) {
			$columnName  = TableNameHelper::getColumnKey($propDef->getName());
			$columnAlias = TableNameHelper::getColumnNameAlias($tableName, $propDef->getName());
			$columns[]   = new ReferenceColumn($tableName, $columnName, $columnAlias, $propDef->getName());
		} else {
			foreach ($dataType->getModelFields() as $modelField) {
				$columnName  = TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey());
				$columnAlias = TableNameHelper::getColumnNameAlias($tableName, $propDef->getName(), $modelField->getKey());
				$columns[]   = new ReferenceColumn($tableName, $columnName, $columnAlias, $propDef->getName(), $modelField->getKey());
			}
		}

		return $columns;
	}

	/**
	 * Create the columns required for the referenced model's properties
	 * @param PropertyDefinition $propDef
	 * @param PropertyDefinition $refModelPropDef
	 * @param string $tableName
	 * @return array
	 * @throws Exception
	 */
	private function getColumnsForReferencePropertyPlan(PropertyDefinition $propDef, PropertyDefinition $refModelPropDef, string $tableName): array
	{
		$dataType = $this->getDataTypeService()->getDefinition($refModelPropDef->getDataType());
		$columns = [];

		if ($dataType->isSimpleStorage()) {
			$columnName  = TableNameHelper::getColumnKey($propDef->getName(), $refModelPropDef->getName());
			$columnAlias = TableNameHelper::getColumnNameAlias($tableName, $propDef->getName(), $refModelPropDef->getName());
			$columns[]   = new Column($tableName, $columnName, $columnAlias);
//			$propertyPlan = new PropertyPlan($refModelPropDef->getModel(), $refModelPropDef->getName(), $columns);
		} else {
			foreach ($dataType->getModelFields() as $modelField) {
				$columnName  = TableNameHelper::getColumnKey($propDef->getName(), $refModelPropDef->getName(), $modelField->getKey());
				$columnAlias = TableNameHelper::getColumnNameAlias($tableName, $propDef->getName(), $refModelPropDef->getName(), $modelField->getKey());
				$columns[]   = new Column($tableName, $columnName, $columnAlias, $modelField->getKey());
			}
//			$propertyPlan = new CompoundPropertyPlan($refModelPropDef->getModel(), $refModelPropDef->getName(), $columns);
		}

//		return $propertyPlan;
		return $columns;
	}

	private function planSingleInlineValueProperty(Query $query, TablePlan $tablePlan, PropertyDefinition $propDef)
	{
		$dataType = $this->getDataTypeService()->getDefinition($propDef->getDataType());

		if ($dataType->isSimpleStorage()) {
			$columnName  = TableNameHelper::getColumnKey($propDef->getName());
			$columnAlias = TableNameHelper::getColumnNameAlias($tablePlan->getTableName(), $propDef->getName());
			$columnPlan  = new PropertyPlan($propDef->getModel(), $propDef->getName(), [new Column($tablePlan->getTableName(), $columnName, $columnAlias)]);
			$tablePlan->addPropertyPlan($propDef->getName(), $columnPlan);
		} else {
			$columns    = array_map(function (DataTypeField $modelField) use ($tablePlan, $propDef) {
				$columnName  = TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey());
				$columnAlias = TableNameHelper::getColumnNameAlias($tablePlan->getTableName(), $propDef->getName(), $modelField->getKey());
				return new Column($tablePlan->getTableName(), $columnName, $columnAlias, $modelField->getKey());
			}, $dataType->getModelFields());
			$columns2 = array_map(function (DataTypeField $modelField) use ($tablePlan, $propDef) {
				$columnName  = TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey());
				$columnAlias = TableNameHelper::getColumnNameAlias($tablePlan->getTableName(), $propDef->getName(), $modelField->getKey());
				return new Column($tablePlan->getTableName(), $columnName, $columnAlias, $modelField->getKey());
			}, $dataType->getModelFields());
			$columnPlan = new CompoundPropertyPlan($propDef->getModel(), $propDef->getName(), $columns2);
			$tablePlan->addPropertyPlan($propDef->getName(), $columnPlan);
		}
	}

	private function planMultiValueProperty(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		echo 'ModelQueryPlanner: ' . $propDef->getModel() . '.' . $propDef->getName() . ' (' . ($propDef->isVirtual() ? 'Virtual':'Normal') . ')<br/>' . PHP_EOL;
		if ($propDef->isVirtual()) $this->planMultiValueVirtualProperty($query, $tablePlan, $modelDef, $propDef);
		else $this->planMultiValueInlineProperty($query, $tablePlan, $modelDef, $propDef);
	}

	private function planMultiValueVirtualProperty(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		$propertyTable = TableNameHelper::getPropertyTableName($modelDef, $propDef);

		$refModelDef      = $this->getModelService()->getModel($propDef->getReference()->getTargetModel())->getDef();
		$refModelTable    = TableNameHelper::getTableNameFromDef($refModelDef);
		$virtualTablePlan = new PropertyTablePlan($refModelDef->getName(), $refModelTable, $refModelTable, $propertyTable, $propertyTable);

		$this->planSingleValueProperties($query, $virtualTablePlan, $refModelDef);
		$this->addPropertyTableWhereConditions($virtualTablePlan, $modelDef, $propertyTable);

		$multiValuePropertyPlan = new MultiValuePropertyPlan($propDef->getModel(), $propDef->getName(), $virtualTablePlan);
		$tablePlan->addPropertyPlan($propDef->getName(), $multiValuePropertyPlan);
//		$tablePlan->addMultiValueProperty($propDef->getName(), $virtualTablePlan);
	}

	private function planMultiValueInlineProperty(Query $query, TablePlan $tablePlan, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		$propertyTable     = TableNameHelper::getPropertyTableName($modelDef, $propDef);
		$propertyTablePlan = new TablePlan($modelDef->getName(), $propertyTable, $propertyTable);

		$this->planSingleInlineValueProperty($query, $propertyTablePlan, $propDef);
		$this->addPropertyTableWhereConditions($propertyTablePlan, $modelDef, $propertyTable);

		$multiValuePropertyPlan = new MultiValuePropertyPlan($propDef->getModel(), $propDef->getName(), $propertyTablePlan);
		$tablePlan->addPropertyPlan($propDef->getName(), $multiValuePropertyPlan);

//		$tablePlan->addMultiValueProperty($propDef->getName(), $propertyTablePlan);
	}

	private function addPropertyTableWhereConditions(TablePlan $tablePlan, ModelDefinitionInterface $modelDef, string $propertyTable): void
	{
		$modelTable = TableNameHelper::getTableNameFromDef($modelDef);
		foreach ($modelDef->getPrimaryKeys() as $primaryKey) {
			$primaryDataType = $this->getDataTypeService()->getDefinition($primaryKey->getDataType());

			if ($primaryDataType->isSimpleStorage()) {
				$columnName = TableNameHelper::getColumnKey($modelTable, $primaryKey->getName());
				$tablePlan->addWhere(new WhereMatch($propertyTable, $columnName, $primaryKey->getName()));
			} else {
				$columns = [];

				foreach ($primaryDataType->getModelFields() as $modelField) {
					echo $propertyTable . ' - ' . $primaryKey->getName() . '<br/>' . PHP_EOL;
					die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
					$columnName  = TableNameHelper::getColumnKey($primaryKey->getName(), $modelField->getKey());
					$columnAlias = TableNameHelper::getColumnNameAlias($propertyTable, $primaryKey->getName(), $modelField->getKey());
					$columns[]   = new Column($propertyTable, $columnName, $columnAlias, $modelField->getKey());
				}
				echo '<pre>';
				print_r($columns);
				die(__FILE__ . ':' . __LINE__ . PHP_EOL);
				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			}
		}
	}

	public function getModelService(): ModelServiceInterface
	{
		return $this->modelService;
	}

	private function getDataTypeService(): DataTypeServiceInterface
	{
		return $this->getModelService()->getRepository()->getDataTypeService();
	}
}