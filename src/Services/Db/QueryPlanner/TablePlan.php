<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Services\Db\EntityCollection;
use WebImage\Models\Services\Db\PropertyLoader;
use WebImage\Models\Services\Db\PropertyLoaderInterface;
use WebImage\Models\Services\RepositoryInterface;

class TablePlan implements SelectQueryBuilderInterface, EntityBuilderInterface
{
	private string $modelName;
	private string $tableName;
	private string $tableAlias;
	private array  $propertyPlans        = []; // Standard non-multi-value properties
	private array  $propertyJoins        = []; // Optional joins for properties that reference virtual tables
	/** @var TablePlan[] A table plan for retrieving multi-valued properties, e.g. TablePlan for simple values for PropertyTablePlan for reference tables */
//	private array  $multiValueProperties = []; // Multi-value properties
//	private array $joins = [];
	private array $where = [];

	public function __construct(string $modelName, string $tableName, string $tableAlias)
	{
		$this->modelName = $modelName;
		$this->tableName = $tableName;
		$this->tableAlias = $tableAlias;
	}

	public function getModelName(): string
	{
		return $this->modelName;
	}

	public function getTableName(): string
	{
		return $this->tableName;
	}

	public function getTableAlias(): string
	{
		return $this->tableAlias;
	}

	/**
	 * @return array<string, SelectQueryBuilderInterface>
	 */
	public function getPropertyPlans(): array
	{
		return $this->propertyPlans;
	}

	public function hasPropertyPlan(string $property): bool
	{
		return array_key_exists($property, $this->propertyPlans);
	}

	public function getPropertyPlan(string $property): SelectQueryBuilderInterface
	{
		if (!array_key_exists($property, $this->propertyPlans)) {
			throw new \InvalidArgumentException('Property "' . $property . '" does not exist');
		}

		return $this->propertyPlans[$property];
	}

//	public function addColumnPlan(string $property, ColumnPlan $columnPlan): void
//	{
//		$this->columnPlans[$property] = $columnPlan;
//	}
	public function addPropertyPlan(string $property, SelectQueryBuilderInterface $columnPlan): void
	{
		$this->propertyPlans[$property] = $columnPlan;
	}

//	public function hasMultiValueProperty(string $property): bool
//	{
//		return array_key_exists($property, $this->multiValueProperties);
//	}

//	public function getMultiValueProperty(string $property): TablePlan
//	{
//		if (!array_key_exists($property, $this->multiValueProperties)) {
//			throw new \InvalidArgumentException('Property "' . $property . '" does not exist');
//		}
//
//		return $this->multiValueProperties[$property];
//	}

//	public function addMultiValueProperty(string $property, TablePlan $propertyTablePlan): void
//	{
//		$this->multiValueProperties[$property] = $propertyTablePlan;
//	}
//
//	public function getMultiValueProperties(): array
//	{
//		return $this->multiValueProperties;
//	}

	public function hasPropertyReference(string $property): bool
	{
		return array_key_exists($property, $this->propertyJoins);
	}

	public function getPropertyReference(string $property): TablePlan
	{
		if (!array_key_exists($property, $this->propertyJoins)) {
			throw new \InvalidArgumentException('Property "' . $property . '" does not exist');
		}

		return $this->propertyJoins[$property];
	}

	public function addReferenceProperty(string $property, TablePlan $propertyTablePlan): void
	{
		$this->propertyJoins[$property] = $propertyTablePlan;
	}

//	public function addJoin(string $joinType, string $joinTable, string $joinTableAlias, string $joinColumn, string $joinTableColumn): void
//	{
//		$this->joins[] = [
//			'joinType' => $joinType,
//			'joinTable' => $joinTable,
//			'joinTableAlias' => $joinTableAlias,
//			'joinColumn' => $joinColumn,
//			'joinTableColumn' => $joinTableColumn
//		];
//	}
//
//	public function getJoins(): array
//	{
//		return $this->joins;
//	}

	public function addWhere(WhereMatchInterface $where): void
	{
		$this->where[] = $where;
	}

	public function getWhere(): array
	{
		return $this->where;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
		$builder->from($this->tableName, $this->tableAlias);
	}

	public function resultsToEntities(RepositoryInterface $repo, ConnectionManager $connectionManager, array $results): EntityCollection
	{
		$propertyLoader = new PropertyLoader($repo, $connectionManager);
		$entities = new EntityCollection($repo->getEntityService(), $propertyLoader);

		foreach($results as $result) {
			$entity = $repo->createEntity($this->getModelName());
			$this->buildEntity($repo, $connectionManager, $entity, $result, $entities);
			$entities[] = $entity;
		}

		return $entities;
	}

	public function buildEntity(RepositoryInterface $repo, ConnectionManager $connectionManager, EntityStub $entityStub, array $result, PropertyLoaderInterface $propertyLoader): void
	{
		foreach($this->getPropertyPlans() as $propertyPlan) {
			if ($propertyPlan instanceof EntityBuilderInterface) {
				$propertyPlan->buildEntity($repo, $connectionManager, $entityStub, $result, $propertyLoader);
			} else {
				echo '<pre>';print_r($propertyPlan); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
			}
		}
	}
}