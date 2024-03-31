<?php
declare(strict_types=1);

namespace WebImage\Models\Services\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\PropertyReferenceDefinition;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use Psr\Log\LoggerInterface;
use WebImage\Models\TypeFields\Type;

class DoctrineTableCreator
{
	/** @var ModelService */
	private $modelService;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * TableCreator constructor.
	 * @param ModelService $typeService
	 */
	public function __construct(ModelService $typeService)
	{
		$this->modelService = $typeService;
	}

	/**
	 * Take a list of ModelDefinition and compare against the existing database schema
	 *
	 * @param ModelDefinition[] $typeDefs
	 * @return SchemaDiff
	 * @throws \Doctrine\DBAL\Exception
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function diffModels(array $typeDefs): SchemaDiff
	{
		$newSchema = new Schema();

		foreach ($typeDefs as $typeDef) {

			$this->assertModelDefinition($typeDef);

			$tableName = TableNameHelper::getTableNameFromDef($typeDef);
			$table     = $newSchema->createTable($tableName);

			$this->createTableColumns($typeDef, $table);
			$this->createMultiValueProperties($typeDef, $newSchema);
			$this->createTableAssociations($typeDef, $table, $newSchema);

			$primaryKeys = $this->getTablePrimaryKeys($typeDef);

			if (count($primaryKeys) > 0) $table->setPrimaryKey($primaryKeys);
		}

		return Comparator::compareSchemas($this->getSchemaManager()->introspectSchema(), $newSchema);
	}

	/**
	 * @param array|ModelDefinition[] $models
	 * @throws \Doctrine\DBAL\Exception
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function importModels(array $models): void
	{
		$diffs = $this->diffModels($models);
		$conn  = $this->modelService->getConnectionManager()->getConnection();
		$diffs->removedTables = [];

		// Remove "Removals"
		foreach ($diffs->changedTables as $tableName => $tableDiff) {
			/**
			 * Reset any diff values that might be destructive, example the removal of columns
			 */
			$tableDiff->removedColumns     = [];
			$tableDiff->removedForeignKeys = [];
			$tableDiff->removedIndexes     = [];
		}

		foreach ($diffs->toSaveSql($conn->getDatabasePlatform()) as $sql) {
			$conn->executeQuery($sql);
		}
	}

	/**
	 * Add columns to the table definition
	 *
	 * @param ModelDefinition $modelDef
	 * @param Table $table
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function createTableColumns(ModelDefinition $modelDef, Table $table)
	{
		foreach ($modelDef->getProperties() as $propDef) {

			if ($propDef->isMultiValued()) continue;

			if ($propDef->isVirtual()) { //} && !$propDef->hasReference()) {
				continue; // Do not do any further processing on virtual fields
			}

			$propertyColumns = TableHelper::getPropertyColumns($this->modelService, $modelDef, $propDef->getName());

			foreach ($propertyColumns->getColumns() as $column) {
				$name     = TableNameHelper::getColumnKey($column->getName());
				$typeName = DoctrineTypeMap::getDoctrineType($column->getDataTypeField()->getType());
				$options  = $this->generateColumnOptions($column, $propDef);

				$tableColumn        = $table->addColumn($name, $typeName, $options);
				$generationStrategy = $propDef->getGenerationStrategy() === null ? '' : strtoupper($propDef->getGenerationStrategy());

				if ($generationStrategy == 'AUTO') {
					$tableColumn->setAutoincrement(true);
				}
			}
		}
	}

	private function generateColumnOptions(TableColumn $tableColumn, PropertyDefinition $propDef): array
	{
		$dataTypeField = $tableColumn->getDataTypeField();

		$options = array_merge(
			[
				'notnull' => $propDef->isReadOnly()
			],
			$dataTypeField->getOptions()->toArray()
		);

		if ($propDef->getSize() > 0) {
			if ($dataTypeField->getType() == 'decimal') {
				$options['precision'] = $propDef->getSize();
				$options['scale']     = $propDef->getSize2();
			} else if ($dataTypeField->getType() == 'string') {
				$options['length'] = $propDef->getSize();
			} else {
				throw new \RuntimeException('Unhandled data type ' . $dataTypeField->getType() . ' with size');
			}
		}

		return $options;
	}

	/**
	 * Set up any multi value fields that result in supplemental table creation (non virtual tables)
	 *
	 * @param ModelDefinition $modelDef
	 * @param Schema $schema
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function createMultiValueProperties(ModelDefinition $modelDef, Schema $schema)
	{
		$tableName = TableNameHelper::getTableNameFromDef($modelDef);

		foreach ($modelDef->getProperties() as $propDef) {

			if ($propDef->isVirtual()) {
//				echo 'Virtual Multiple: ' . $propDef->getType() . '.' . $propDef->getName() . PHP_EOL;
				continue;
			}

			if (!$propDef->isMultiValued()) continue;

			$propertyTableName = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
			$columns           = TableHelper::getPropertiesColumns($this->modelService, $modelDef, $propDef->getName());
			$propertyTable     = $schema->createTable($propertyTableName);
			$primaryKeys       = [];

			/**
			 * Add columns for multi-valued property
			 */
			foreach ($columns->getColumns() as $column) {
				$name     = $column->getName();
				$typeName = DoctrineTypeMap::getDoctrineType($column->getDataTypeField()->getType());
				$options  = $this->generateColumnOptions($column, $propDef);

				$propertyTable->addColumn($name, $typeName, $options);
			}

			/**
			 * Add primary key references
			 */
			$primaryKey = [];
			foreach ($modelDef->getPrimaryKeys()->keys() as $primaryKey) {
				$columns = TableHelper::getPropertiesColumns($this->modelService, $modelDef, $primaryKey);

				foreach ($columns->getColumns() as $primaryKeyColumn) {
					$name    = TableNameHelper::getColumnKey($tableName, $primaryKeyColumn->getName());
					$type    = DoctrineTypeMap::getDoctrineType($primaryKeyColumn->getDataTypeField()->getType());
					$options = $this->generateColumnOptions($primaryKeyColumn, $propDef);

					$propertyTable->addColumn($name, $type, $options);
					$primaryKeys[] = $name;
				}
			}

			$propertyTable->setPrimaryKey($primaryKeys);
		}
	}

	/**
	 * Create tables and columns necessary for relating one Model to another
	 *
	 * @param ModelDefinition $modelDef
	 * @param Table $table
	 * @param Schema $newSchema
	 */
	private function createTableAssociations(ModelDefinition $modelDef, Table $table, Schema $newSchema)
	{
		echo 'Model: ' . $modelDef->getName() . PHP_EOL;
		foreach ($modelDef->getProperties() as $propDef) {
			if (!$propDef->isVirtual() || !$propDef->hasReference()) continue;

			$reference   = $propDef->getReference();
			$otherModel  = $this->modelService->getModel($reference->getTargetModel());
			$cardinality = PropertyReferenceHelper::getAssociationCardinality($this->modelService, $propDef);

			if ($otherModel === null) {
				throw new \Exception($propDef->getModel() . '.' . $propDef->getName() . ' references ' . $reference->getTargetModel() . ', but ' . $reference->getTargetModel() . ' does not exist');
			}

			$handled = $cardinality->isOneToOne() || $cardinality->isManyToMany() || $cardinality->isOneToMany();
			echo '  ' . $modelDef->getPluralName() . '.' . $propDef->getName() . ' ' . ($propDef->isVirtual()?'VIRTUAL ':'') . ($propDef->hasReference()?'REFERENCE ':'') . $cardinality . ' (' . ($handled ? 'Handled' : 'Unhandled') . ')' . PHP_EOL;

			if ($cardinality->isOneToOne()) {
				$this->createOneToOneProperty($table, $modelDef, $propDef);
			} else if ($cardinality->isOneToMany()) {
				$this->createAssociationTable($propDef, $newSchema);
			// Nothing to be done, as this will be handled by the associated type in manyToOne?
			// @TODO should a JOIN table be created to handle this on some occasions?
			} else if ($cardinality->isManyToOne()) {
				if ($reference->getReverseProperty() === null) { // When "reverse property" is set then that model will setup the required association table
					throw new \Exception($cardinality . ' is not implemented');
				}
			} else if ($cardinality->isManyToMany()) {
				$this->createAssociationTable($propDef, $newSchema);
			} else {
				throw new \Exception('Unhandled cardinality ' . $cardinality . ' on ' . $propDef->getModel() . '.' . $propDef->getName() . ' -> ' . $reference->getTargetModel() . ($reference->getReverseProperty() === null ? '' : '.' . $reference->getReverseProperty()));
			}
		}
//		echo $modelDef->getName() . '.createReverse' . PHP_EOL;
//		$this->createReversePropertyReferences($modelDef, $table, $newSchema);
	}

	private function createOneToOneProperty(Table $table, ModelDefinitionInterface $modelDef, PropertyDefinition $propDef)
	{
		$modelService  = $this->modelService;
		$reference     = $propDef->getReference();
		$otherModelDef = $modelService->getModel($reference->getTargetModel())->getDef();

		if ($reference->getReverseProperty() === null) {
			echo '  - createOneToOneProperty: ' . $reference->getTargetModel() . '.' . ($reference->getReverseProperty() === null ? 'ALL' : $reference->getReverseProperty()) . PHP_EOL;

			$columns = TableHelper::getPropertyColumns($modelService, $modelDef, $propDef->getName());

			foreach ($columns->getColumns() as $primaryKey) {
				$name    = $primaryKey->getName();
				$type    = DoctrineTypeMap::getDoctrineType($primaryKey->getDataTypeField()->getType());
				$options = $this->generateColumnOptions($primaryKey, $propDef);
				$table->addColumn($name, $type, $options);
			}

		} else {
			throw new \Exception('Unhandled 1:1 with reverse property on ' . $propDef->getModel() . '.' . $propDef->getName() . ' -> ' . $reference->getTargetModel() . '.' . $reference->getReverseProperty());
		}
	}

	/**
	 * Create columns required for other Models to reference this Model
	 *
	 * @param ModelDefinition $typeDef
	 * @param Table $table
	 * @param Schema $newSchema
	 */
	private function createReversePropertyReferences(ModelDefinition $typeDef, Table $table, Schema $newSchema)
	{
		$dictionaryService = $this->modelService->getRepository()->getDictionaryService();

		/**
		 * Check for references from other types to this type and create any additional reference columns necessary
		 */
		foreach ($dictionaryService->getModels() as $otherModelDef) {
			if ($otherModelDef->getPluralName() == $typeDef->getPluralName()) continue;

			foreach ($otherModelDef->getProperties() as $otherModelPropDef) {
				if (!$otherModelPropDef->isVirtual() || $otherModelPropDef->getReference()->getTargetModel() != $typeDef->getName()) continue;

				$cardinality = PropertyReferenceHelper::getAssociationCardinality($this->modelService, $otherModelPropDef);

				if ($cardinality->isOneToOne() || $cardinality->isManyToMany()) {
					continue;
				} else if ($cardinality->isManyToOne()) {
					throw new \Exception($otherModelPropDef->getModel() . '.' . $otherModelPropDef . ' has unsupported cardinality: ' . $cardinality);
				}

				$this->createPropertyAssociationTable($otherModelDef, $otherModelPropDef, $typeDef, $newSchema);
			}
		}
	}

	private function createPropertyAssociationTable(ModelDefinition $modelDef, PropertyDefinition $propDef, ModelDefinition $targetModelDef, Schema $newSchema)
	{
		$tableName     = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
		$sourcePropertyColumns = TableHelper::getPrimaryKeyColumns($this->modelService, $modelDef);

		$targetPropertyColumns = TableHelper::getPrimaryKeyColumns($this->modelService, $targetModelDef);

		if ($newSchema->hasTable($tableName)) return;

		$propTable = $newSchema->createTable($tableName);

		$targetTableColumns = [$sourcePropertyColumns->getColumns(), $targetPropertyColumns->getColumns()];

		/** @var TableColumn[] $targetColumns */
		foreach($targetTableColumns as $targetColumns) {
			$foreignTable = null;
			$localColumnNames = [];
			$foreignColumnNames = [];
			foreach($targetColumns as $column) {
				$foreignTable = $column->getTableName();
				$columnName = TableNameHelper::getColumnKey($column->getTableName(), $column->getName());
				$type       = DoctrineTypeMap::getDoctrineType($column->getDataTypeField()->getType());
				$options    = $column->getDataTypeField()->getOptions()->toArray();


				$propTable->addColumn($columnName, $type, $options);
				$localColumnNames[] = $columnName;
				$foreignColumnNames[] = $column->getName();
			}

			$foreignKeyName = sprintf('fk_%s_%s', $tableName, $foreignTable);
			$propTable->addForeignKeyConstraint($foreignTable, $localColumnNames, $foreignColumnNames);
		}
	}

	/**
	 * Create table that can are used to join multiple tables
	 * @param PropertyDefinition $propDef
	 * @param Schema $newSchema
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	private function createAssociationTable(PropertyDefinition $propDef, Schema $newSchema)
	{
		$typeService = $this->modelService;
		$association = TableHelper::getAssociationTable($typeService, $propDef->getModel(), $propDef->getReference()->getTargetModel(), $propDef->getName(), $propDef->getReference()->getReverseProperty());

		if (!$newSchema->hasTable($association->getTableName())) {

			$associationTable = $newSchema->createTable($association->getTableName());

			$indexes = [];

			/** @var AssociationTableTarget[] $targetTables */
			$targetTables = [$association->getSource(), $association->getTarget()];

			foreach ($targetTables as $targetTable) {

				$indexName    = 'ix_' . $targetTable->getTableName();
				$indexColumns = [];

				/**
				 * Add Columns
				 */
				foreach ($targetTable->getPropertyColumns()->getColumns() as $column) {
					$name = TableNameHelper::getColumnKey($targetTable->getTableName(), $column->getName());
					$type    = DoctrineTypeMap::getDoctrineType($column->getDataTypeField()->getType());
					$options = $column->getDataTypeField()->getOptions()->toArray();
					$associationTable->addColumn($name, $type, $options);

					$indexColumns[] = $name;
				}

				/**
				 * Prepare indexes
				 */
				foreach ($targetTables as $targetTable2) {
					if ($targetTable2->getTableName() == $targetTable->getTableName()) continue;

					$indexName .= '_' . $targetTable2->getTableName();

					foreach ($targetTable2->getPropertyColumns()->getColumns() as $column) {
						$indexColumns[] = TableNameHelper::getColumnKey($targetTable2->getTableName(), $column->getName());
					}
				}

				$indexes[$indexName] = $indexColumns;
			}

			foreach ($indexes as $indexName => $indexColumns) {
				$associationTable->addIndex($indexColumns, $indexName);
			}
		}
	}

	/**
	 * Get the primary key for a given typeDef
	 *
	 * @param ModelDefinition $modelDef
	 * @return array
	 */
	private function getTablePrimaryKeys(ModelDefinition $modelDef)
	{
		$primaryKeys = [];

		// Iterate through each key and re-assign referenced property type to their appropriate columns
		foreach ($modelDef->getPrimaryKeys()->keys() as $primaryKey) {
			$propertyDef = $modelDef->getProperty($primaryKey);

			if ($propertyDef === null) {
				throw new \Exception($modelDef->getPluralName() . ' does not have a primary key property named ' . $primaryKey);
			}

			if ($propertyDef->isVirtual() && $propertyDef->getReference() !== null) {

				$referencedModelDef = $this->modelService->getModel($propertyDef->getReference()->getTargetModel())->getDef();
				$referencedModelKey = $referencedModelDef->getPrimaryKeys()->keys();

				// Use primary keys of refernced object as the primary keys for this property
				foreach ($referencedModelKey as $primaryKey) {
					$referencedPrimaryKeyProperty = $referencedModelDef->getProperty($primaryKey);
					$propertyColumns              = TableHelper::getPropertyColumns($this->modelService, $referencedModelDef, $referencedPrimaryKeyProperty->getName());

					foreach ($propertyColumns->getColumns() as $column) {
						$name          = TableNameHelper::getColumnKey($propertyDef->getName(), $column->getName());
						$primaryKeys[] = $name;
					}
				}
			} else {
				$primaryKeys[] = TableNameHelper::getColumnKey($primaryKey);
			}
		}

//		if (count($primaryKeys) === 0) {
//			throw new \Exception(sprintf('%s model is missing a primary key', $modelDef->getName()));
//		}

		return $primaryKeys;
	}

	private function assertModelDefinition(ModelDefinition $diff)
	{
	}

	private function getSchemaManager(): AbstractSchemaManager
	{
		return $this->modelService->getConnectionManager()->getConnection()->createSchemaManager();
	}

	private function log(string $text, int $level = 0)
	{
		if ($this->logger === null) return;

		$indent = 2;
		$this->logger->debug(str_repeat(' ', $level * $indent) . $text);
	}
}
