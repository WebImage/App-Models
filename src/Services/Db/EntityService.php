<?php

namespace WebImage\Models\Services\Db;

use Exception;
use WebImage\Core\ArrayHelper;
use WebImage\Core\Collection;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Exceptions\InvalidPropertyException;
use WebImage\Models\Helpers\EntityDebugger;
use WebImage\Models\Properties\MultiValuePropertyInterface;
use WebImage\Models\Services\Db\ModelTableDebugger;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Query\Query;
use WebImage\Models\Services\AbstractEntityService;
use WebImage\Models\Services\UnsupportedMultiValueProperty;

class EntityService extends AbstractEntityService
{
	/** @var ConnectionManager */
	private ConnectionManager $connectionManager;
	/** @var TableNameHelper */
	private TableNameHelper $tableHelper;

	/**
	 * EntityService constructor.
	 * @param ConnectionManager $connectionManager
	 */
	public function __construct(ConnectionManager $connectionManager)
	{
		$this->setConnectionManager($connectionManager);
	}

	/**
	 * @return ConnectionManager
	 */
	public function getConnectionManager(): ConnectionManager
	{
		return $this->connectionManager;
	}

	/**
	 * @param ConnectionManager $connectionManager
	 */
	public function setConnectionManager(ConnectionManager $connectionManager): void
	{
		$this->connectionManager = $connectionManager;
	}

	public function get(): Entity
	{
		// TODO: Implement get() method.
		throw new \RuntimeException('Not implemented');
	}

	/**
	 * @throws Exception
	 * @throws Exception
	 */
	public function save(Entity $entity): Entity
	{
		list($data, $params) = $this->prepareSave($entity);
		$modelDef  = $this->getRepository()->getModelService()->getModel($entity->getModel())->getDef();
		$tableName = TableNameHelper::getTableNameFromDef($modelDef);
		$qb        = $this->getConnectionManager()->createQueryBuilder();

		if ($entity->isNew()) {
			$qb->insert($tableName);
			$qb->values($data);
		} else {
			$qb->update($tableName);

			foreach ($data as $key => $val) {
				$qb->set($key, ':' . $key);
			}

			$primaryKeyColumns = TableHelper::getPrimaryKeyColumns($this->getRepository()->getModelService(), $modelDef);

			foreach ($primaryKeyColumns->getProperties() as $property => $modelPropertyTableColumns) {

				foreach ($modelPropertyTableColumns->getColumns() as $propertyColumn) {

					$colName  = TableNameHelper::getTableColumnName($tableName, $propertyColumn->getName());
					$colAlias = TableNameHelper::getColumnNameAlias($tableName, $propertyColumn->getName());

					$qb->andWhere(sprintf('%s = :%s', $colName, $colAlias));

					if ($modelPropertyTableColumns->getReferencedModel() === null) {
						$params[$colAlias] = $entity[$property];
					} else {
						$modelValues       = $entity[$modelPropertyTableColumns->getReferencedModel()];
						$params[$colAlias] = $modelValues === null ? null : $modelValues[$modelPropertyTableColumns->getReferencedProperty()];
					}
				}
			}
		}

		$this->getRepository()->getEventManager()->trigger(self::EVENT_SAVING, $entity, $this);

		$connection = $this->getConnectionManager()->getConnection();
		$connection->beginTransaction();
		$qb->setParameters($params);
		$qb->executeStatement();
		$lastId = $connection->lastInsertId();

		// Update the entity's ID
		if ($entity->isNew()) {
			$primaryKeyColumns = TableHelper::getPrimaryKeyColumns($this->getRepository()->getModelService(), $modelDef);

			foreach ($primaryKeyColumns->getProperties() as $property => $propertyColumns) {
				$value = null;
				if ($entity[$property] === null) $entity[$property] = $lastId;
			}
			$entity->setIsNew(false);
		}

		$this->saveMultiValuedProperties($entity);
//		echo 'Bailing on: ' . $entity->getModel() . '<br/>' . PHP_EOL;
//		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
		$connection->commit();

		$this->getRepository()->getEventManager()->trigger(self::EVENT_SAVED, $entity, $this);

		return $entity;
	}

	/**
	 * @throws Exception
	 */
	private function saveMultiValuedProperties(Entity $entity)
	{
		$modelService     = $this->getRepository()->getModelService();
		$dataTypeService  = $this->getRepository()->getDataTypeService();
		$modelDef         = $modelService->getModel($entity->getModel())->getDef();
		$modelTable       = TableNameHelper::getTableNameFromDef($modelDef);
		$modelPrimaryKeys = TableHelper::getPrimaryKeyColumns($modelService, $modelDef);

		foreach ($modelDef->getProperties() as $propDef) {
			if (!$propDef->isMultiValued()) continue;
			if ($propDef->hasReference() && $propDef->getReference()->getReverseProperty() !== null) throw new UnsupportedMultiValueProperty('Saving multi-valued properties with a reverse property is not currently supported: ' . $propDef->getModel() . '.' . $propDef->getName());
			$property = $entity->getProperty($propDef->getName());
			if (!($property instanceof MultiValuePropertyInterface)) {
				throw new InvalidPropertyException('Property ' . $propDef->getName() . ' is not a multi-valued property');
			}
			$dataType = $dataTypeService->getDefinition($propDef->getDataType());
			$values   = $property->getValues();

			$foundIndexes = [];
			foreach ($property->getOriginalValues() as $originalIx => $originalValue) {
				foreach ($property->getValues() as $ix => $value) {
					if ($originalValue === $value) {
						$foundIndexes[$ix] = $originalIx;
						continue 2;
					}
				}
				throw new UnsupportedMultiValueProperty('Saving multi-valued properties with existing values is not yet supported: ' . $propDef->getModel() . '.' . $propDef->getName());
				// @TODO Delete record
				#$this->getConnectionManager()->getConnection()->delete($modelTable, $originalValue);
			}

			foreach ($property->getValues() as $ix => $value) {
				if (array_key_exists($ix, $foundIndexes)) continue; // Already exists, skip

				$data = [];
				$params   = [];

				// Add entity primary key values to record
				foreach($modelPrimaryKeys->getProperties() as $key => $modelPrimaryKey) {
					$primaryKeyValue = $entity[$key];
					foreach($modelPrimaryKey->getColumns() as $column) {
						$dbKey = TableNameHelper::getColumnKey($modelPrimaryKey->getTable(), $column->getName());
						$data[$dbKey] = ':' . $dbKey;
						$primaryKeyValue = $column->getDataTypeField()->getKey() === null ? $primaryKeyValue : $primaryKeyValue[$column->getDataTypeField()->getKey()] ?? null;
						$params[$dbKey] = $primaryKeyValue;
					}
				}
//					$this->getConnectionManager()->getConnection()->insert($modelTable, $value);
				if ($propDef->isVirtual()) {
					if (!($value instanceof EntityStub)) throw new UnsupportedMultiValueProperty('Cannot save virtual multi-valued properties without a reference: ' . $propDef->getModel() . '.' . $propDef->getName());

					$refPrimaryKeys = TableHelper::getPrimaryKeyColumns($modelService, $modelService->getModel($propDef->getReference()->getTargetModel())->getDef());
					foreach($refPrimaryKeys->getProperties() as $key => $property) {
						$refValue = $value[$key];
						foreach($property->getColumns() as $column) {
							$dbKey = TableNameHelper::getColumnKey($propDef->getName(), $column->getName());
							$data[$dbKey] = ':' . $dbKey;
							$refValue = $column->getDataTypeField()->getKey() === null ? $refValue : $refValue[$column->getDataTypeField()->getKey()] ?? null;
							$params[$dbKey] = $refValue;
						}
					}

					$valueTable = TableNameHelper::getPropertyTableName($modelDef, $propDef);
					echo 'Value Table: ' . $valueTable . '<br/>' . PHP_EOL;
					echo '<pre>';
					print_r($data);
					print_r($params);

					$qb = $this->getConnectionManager()->createQueryBuilder();
					$qb->insert($valueTable);
					$qb->values($data);
					$qb->setParameters($params);
					$qb->executeStatement();

					echo 'INSERT: ' . $valueTable . '<br/>' . PHP_EOL;
				} else {
					$valueTable = TableNameHelper::getPropertyTableName($modelDef, $propDef);
					$columns = TableHelper::getPropertyColumns($modelService, $modelDef, $propDef->getName());

					// Add key values to record
					foreach ($columns->getColumns() as $column) {
						$data[$column->getName()] = ':' . $column->getName();
						$params[$column->getName()]   = $value[$column->getDataTypeField()->getKey()] ?? null;
					}
					$qb = $this->getConnectionManager()->createQueryBuilder();
					$qb->insert($valueTable);
					$qb->values($data);
					$qb->setParameters($params);
					$qb->executeStatement();
					echo 'INSERT: ' . $valueTable . '<br/>' . PHP_EOL;
					echo '<pre>';print_r($data);print_r($params);echo '</pre><hr>';
				}
			}

			continue;
			/**
			 * Four scenarios
			 * [ ] [Virtual][Simple]
			 * [ ] [Virtual][Complex]
			 * [ ] [Inline][Simple]
			 * [ ] [Inline][Complex]
			 */
//			if ($entity->getProperty($propDef->getName()) instanceof MultiValuePropertyInterface) {
//				$values = $entity->getProperty($propDef->getName())->getValues();
			echo '<pre>';
			print_r($values);
			die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//			} else {
//				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			$values = $entity[$propDef->getName()];
//			}
//			$values = $entity[$propDef->getName()];

			if ($propDef->isVirtual()) {
				echo '[Virtual]';
			} else {
//				$propertyTableName = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
				$entityPrimaryKeys = TableHelper::getPrimaryKeyColumns($modelService, $modelDef);

				foreach ($values as $value) {
					$params   = [];
					$data = [];

					/**
					 * Assign primary key from entity to the mapped values
					 */
					foreach ($entityPrimaryKeys->getProperties() as $propertyKey => $propertyTableColumns) {
						if (!isset($entity[$propertyKey]) || $entity[$propertyKey] === null || (is_string($entity[$propertyKey]) && strlen($entity[$propertyKey]) === 0)) throw new InvalidPropertyException('Missing value for ' . $propertyKey . ' on ' . $entity->getModel());

						foreach ($propertyTableColumns->getColumns() as $column) {
							$colName  = TableNameHelper::getColumnKey($modelTable, $column->getName(), $column->getDataTypeField()->getKey());
							$colValue = $entity[$propertyKey];
							if ($column->getDataTypeField()->getKey() !== null) {
								$colValue = $colValue[$column->getDataTypeField()->getKey()];
							}
							$params[$colName]   = ':' . $colName;
							$data[$colName] = $colValue;
						}
					}

					/**
					 * Assign data values to save
					 */
					if ($dataType->isSimpleStorage()) {
						die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
					} else {
						foreach ($dataType->getModelFields() as $modelField) {
							$subKey           = $modelField->getKey();
							$colName          = TableNameHelper::getColumnKey($propDef->getName(), $subKey);
							$colValue         = $value[$subKey];
							$params[$colName]   = ':' . $colName;
							$data[$colName] = $colValue;
						}
					}
				}
				echo '<pre>';
				print_r($entityPrimaryKeys);
				die(__FILE__ . ':' . __LINE__ . PHP_EOL);
				$propertyColumns = TableHelper::getPropertiesColumns($modelService, $modelDef, $propDef->getName());
				/*
				 * $sourcePropertyColumns = TableHelper::getPrimaryKeyColumns($this->modelService, $modelDef);
						$targetPropertyColumns = TableHelper::getPrimaryKeyColumns($this->modelService, $targetModelDef);

						if ($newSchema->hasTable($propertyTableName)) return;

						$propTable = $newSchema->createTable($propertyTableName);

						$targetTableColumns = [$sourcePropertyColumns->getColumns(), $targetPropertyColumns->getColumns()];
				 */
				echo '[Inline]';
				echo '<pre>';
				print_r($dataType->getModelFields());
				foreach ($values as $value) {
					print_r($value);
				}

				echo 'Property Table: ' . $propertyTableName . '<br/>' . PHP_EOL;

				print_r($propertyColumns);
				echo '</pre>';
			}
			if ($dataType->isSimpleStorage()) {
				echo '[Simple]';
			} else {
				echo '[Complex]';
			}
			echo '<br/>';
			//				foreach($property->getValues() as $valueEntity) {
//					if ($valueEntity instanceof EntityStub) {
//
//						$propertyTableName = TableNameHelper::getTableNameFromDef($modelDef, $property->getDef()->getName());
//						$columns = TableHelper::getPropertiesColumns($modelService, $modelDef, $property->getDef()->getName());

		}
		#foreach($modelColumns->getPropertyColumns())
//		foreach($modelPrimaryColumns as $modelPrimaryColumn) {
//			$propertyType = $this->getRepository()->getDictionaryService()->getPropertyType(
//				$modelPrimaryColumn->getDataTypeField()->getType()
//			);
//
//			if ($propertyType->isSimpleStorage()) {
////				TableNameHelper::ge
//				echo '<pre>';print_r($modelPrimaryColumn); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//			} else {
//				throw new UnsupportedMultiValueProperty('Cannot save multi-valued properties yet where entity ' . $entity->getModel() . ' does not have simple storage.');
//			}
//			echo '<pre>';print_r($propertyType); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//		}
//		echo '<pre>';print_r($modelPrimaryColumns); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//
//		foreach($entity->getProperties() as $property) {
//			if (!$property->getDef()->isMultiValued()) continue;
//
//			if (!$property->getDef()->isVirtual()) throw new UnsupportedMultiValueProperty('Cannot save non-virtual multi-valued properties yet: ' . $property->getDef()->getModel() . '.' . $property->getDef()->getName());
//			else if (!$property->getDef()->hasReference()) throw new UnsupportedMultiValueProperty('Cannot save virtual multi-valued properties without a reference: ' . $property->getDef()->getModel() . '.' . $property->getDef()->getName());
//
//				foreach($property->getValues() as $valueEntity) {
//					if ($valueEntity instanceof EntityStub) {
//
//						$propertyTableName = TableNameHelper::getTableNameFromDef($modelDef, $property->getDef()->getName());
//						$columns = TableHelper::getPropertiesColumns($modelService, $modelDef, $property->getDef()->getName());
//
//						$data = [];
//						$values = [];
//
//						foreach($columns->getColumns() as $column) {
//
//							echo '<pre>';print_r($column->getReferencedColumnName()); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
////							$colName = $column->getName()
//						}
//						echo '<pre>';
//						echo 'Table: ' . $propertyTableName . '<br/>' . PHP_EOL;
//						print_r($columns);
//
//						#$entity->getRepository()->getEntityService()->save($value);
//
//					}
//				}
//				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
//				echo '<pre>';print_r($property); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//				echo '<pre>';
//				print_r($property->getDef());
//				echo $property->getDef()->getModel() . '.' . $property->getDef()->getName() . ' is multi-valued' . '<br/>' . PHP_EOL;
//
//		}
	}

	/**
	 * @throws Exception
	 */
	private function prepareSave(Entity $entity): array
	{
		$modelDef = $this->getRepository()->getModelService()->getModel($entity->getModel())->getDef();

		$data   = [];
		$params = [];

		foreach ($modelDef->getProperties() as $propDef) {
			if ($propDef->isMultiValued()) continue; // Multivalued properties are saved after the entity is saved to ensure proper association
			else if ($propDef->isVirtual() && $propDef->getReference() === null) continue; // No point in saving virtual properties that do not have a referenced model

			$dataType = $this->getRepository()->getDataTypeService()->getDefinition($propDef->getDataType());
			$columns  = TableHelper::getPropertiesColumns($this->getRepository()->getModelService(), $modelDef, $propDef->getName())->getPropertyColumns($propDef->getName());

			if ($propDef->isVirtual()) {
				$modelService  = $this->getRepository()->getModelService();
				$targetModel   = $propDef->getReference()->getTargetModel();
				$model         = $modelService->getModel($targetModel);
				$refModelDef   = $model->getDef();
				$refModelProps = $refModelDef->getProperties();
				$entityRef     = $entity->getProperty($propDef->getName())->getValue();

				if ($entityRef instanceof EntityStub) {
					foreach ($refModelProps as $refModelPropDef) {
						if (!$refModelPropDef->isPrimaryKey()) continue;

						$value = $entityRef->getPropertyValue($refModelPropDef->getName());
						if (is_array($value)) {
							throw new Exception('Value of type array for EntityReference is not supported');
						}

						foreach ($columns->getColumns() as $column) {
							$subKey   = $column->getDataTypeField()->getKey();
							$colParam = $column->getName() . ($subKey === null ? '' : '_' . $subKey);

							$data[$colParam]   = ':' . $colParam;
							$params[$colParam] = $subKey !== null && is_array($value) ? $value[$subKey] : $value;
						}
					}
				}
			} else {
				$value = $entity->getPropertyValue($propDef->getName());
				if ($dataType->isSimpleStorage()) {
					foreach ($columns->getColumns() as $column) {
						$data[$column->getName()]   = ':' . $column->getName();
						$params[$column->getName()] = $this->getRepository()->getDataTypeService()->valueForStorage($propDef->getDataType(), $value);
					}
				} else if ($value !== null) { // Don't save explicit NULL values (unless they are part of the compound array)
					if (!is_array($value) || !ArrayHelper::isAssociative($value)) throw new InvalidPropertyException($propDef->getName() . ' was expecting an array of values');

					$value = $this->getRepository()->getDataTypeService()->valueForStorage($propDef->getDataType(), $value);
					foreach ($columns->getColumns() as $column) {
						$data[$column->getName()]   = ':' . $column->getName();
						$params[$column->getName()] = array_key_exists($column->getDataTypeField()->getKey(), $value) ? $value[$column->getDataTypeField()->getKey()] : null;
					}
				}
			}
		}

		return [$data, $params];
	}

	/**
	 * @param string $modelName
	 * @return EntityReference
	 */
	protected function createEntityReference(string $modelName): EntityReference
	{
		return new DbEntityReference($modelName);
	}

	/**
	 * @throws Exception
	 */
	public function delete(Entity $entity): bool
	{
		throw new Exception(sprintf('%s is not yet supported', __METHOD__));
	}

	/**
	 * @return Collection|Entity[]
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function query(Query $query): Collection
	{
		$queryService = new EntityQueryService($this);

		return $queryService->query($query);
	}

//	public function getResultHelper(): ResultHelper2
//	{
//		return new ResultHelper2($this->getRepository()->getDataTypeService());
//	}
}
