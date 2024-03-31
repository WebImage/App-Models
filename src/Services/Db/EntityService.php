<?php

namespace WebImage\Models\Services\Db;

use WebImage\Core\Collection;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Query\Query;
use WebImage\Models\Services\AbstractEntityService;
use WebImage\Models\Services\UnsupportedMultiValueProperty;

class EntityService extends AbstractEntityService
{
	/** @var ConnectionManager */
	private $connectionManager;
	/** @var TableNameHelper */
	private $tableHelper;
	/**
	 * EntityService constructor.
	 * @param ConnectionManager $connectionManager
	 */
	public function __construct(ConnectionManager $connectionManager, TableNameHelper $tableHelper)
	{
		$this->setConnectionManager($connectionManager);
		$this->setTableHelper($tableHelper);
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

	/**
	 * @return TableNameHelper
	 */
	public function getTableHelper(): TableNameHelper
	{
		return $this->tableHelper;
	}

	/**
	 * @param TableNameHelper $tableHelper
	 */
	public function setTableHelper(TableNameHelper $tableHelper): void
	{
		$this->tableHelper = $tableHelper;
	}

	public function get(): Entity
	{
		// TODO: Implement get() method.
		throw new \RuntimeException('Not implemented');
	}

	public function save(Entity $entity): Entity
	{
		$connection = $this->getConnectionManager()->getConnection();
		$qb = $this->getConnectionManager()->createQueryBuilder();

		$connection->beginTransaction();

		$modelDef = $this->getRepository()->getModelService()->getModel($entity->getModel())->getDef();

		$data = [];
		$params = [];

		foreach($modelDef->getProperties() as $propDef) {
			if ($propDef->isMultiValued()) throw new UnsupportedMultiValueProperty('Cannot save multi-valued properties yet');
			else if ($propDef->isVirtual() && $propDef->getReference() === null) continue;

			$columns = TableHelper::getPropertiesColumns($this->getRepository()->getModelService(), $modelDef, $propDef->getName())->getPropertyColumns($propDef->getName());

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
							throw new \Exception('Value of type array for EntityReference is not currently supported');
						}

						foreach($columns->getColumns() as $column) {
							$subKey = $column->getDataTypeField()->getKey();
							$colParam = $column->getName() . ($subKey === null ? '' : '_' . $subKey);

							$data[$colParam] = ':' . $colParam;
							$params[$colParam] = $subKey !== null && is_array($value) ? $value[$subKey] : $value;
						}
					}
				}
			} else {
				$value = $entity->getPropertyValue($propDef->getName());

				if (is_array($value)) {
					throw new \Exception('Unhandled array value for ' . $propDef->getModel() . '.' . $propDef->getName());
				}

				foreach($columns->getColumns() as $column) {
					$data[$column->getName()]   = ':' . $column->getName();
					$params[$column->getName()] = $this->getRepository()->getDataTypeService()->valueForStorage($propDef->getDataType(), $value);
				}
			}
		}

		$tableName = TableNameHelper::getTableNameFromDef($modelDef);

		if ($entity->isNew()) {
			$qb->insert($tableName);
			$qb->values($data);
		} else {
			$qb->update($tableName);

			foreach($data as $key => $val) {
				$qb->set($key, ':' . $key);
			}

			$primaryKeyColumns = TableHelper::getPrimaryKeyColumns($this->getRepository()->getModelService(), $modelDef);

			foreach($primaryKeyColumns->getProperties() as $property => $modelPropertyTableColumns) {

				foreach($modelPropertyTableColumns->getColumns() as $propertyColumn) {

					$colName = TableNameHelper::getColumnName($tableName, $propertyColumn->getName());
					$colAlias = TableNameHelper::getColumnNameAlias($tableName, $propertyColumn->getName());

					$qb->andWhere(sprintf('%s = :%s', $colName, $colAlias));

					if ($modelPropertyTableColumns->getReferencedModel() === null) {
						$params[$colAlias] = $entity[$property];
					} else {
						$modelValues = $entity[$modelPropertyTableColumns->getReferencedModel()];
						$params[$colAlias] = $modelValues === null ? null : $modelValues[$modelPropertyTableColumns->getReferencedProperty()];
					}
				}
			}
		}

		$this->getRepository()->getEventManager()->trigger(self::EVENT_SAVING, $entity, $this);

		$qb->setParameters($params);
		$qb->executeStatement();
		$lastId = $connection->lastInsertId();
		$connection->commit();

		// Update the entity's ID
		if ($entity->isNew()) {
			$primaryKeyColumns = TableHelper::getPrimaryKeyColumns($this->getRepository()->getModelService(), $modelDef);

			foreach($primaryKeyColumns->getProperties() as $property => $propertyColumns) {
				$value = null;
				if ($entity[$property] === null) $entity[$property] = $lastId;
			}
			$entity->setIsNew(false);
		}

		$this->getRepository()->getEventManager()->trigger(self::EVENT_SAVED, $entity, $this);

		return $entity;
	}

	public function delete(Entity $entity): bool
	{
		throw new \Exception(sprintf('%s is not yet supported', __METHOD__));
	}

	/**
	 * @param Query $query
	 * @return Collection|Entity[]
	 */
	public function query(Query $query): Collection
	{
		$queryService = new EntityQueryService($this);

		return $queryService->query($query);
	}

	public function getResultHelper(): ResultHelper
	{
		return new ResultHelper($this->getRepository()->getDataTypeService());
	}
}
