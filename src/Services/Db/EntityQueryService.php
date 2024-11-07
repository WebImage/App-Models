<?php

namespace WebImage\Models\Services\Db;

use Doctrine\DBAL\Exception;
use RuntimeException;
use WebImage\Core\Collection;
use WebImage\Db\ConnectionManager;
use WebImage\Db\QueryBuilder as DbQueryBuilder;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\Property;
use WebImage\Models\Query\CompositeFilter;
use WebImage\Models\Query\Filter;
use WebImage\Models\Query\Property as QueryProperty;
use WebImage\Models\Query\Query;
use WebImage\Models\Services\Db\QueryPlanner\Column;
use WebImage\Models\Services\Db\QueryPlanner\PropertyPlan;
use WebImage\Models\Services\Db\QueryPlanner\ModelQueryPlanner;
use WebImage\Models\Services\Db\QueryPlanner\TablePlan;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Models\Services\UnsupportedMultiColumnKeys;

class EntityQueryService
{
	/** @var EntityService */
	private EntityService $entityService;
//	private ModelQueryPlanner $modelQueryPlanner;

	public function __construct(EntityService $entityService)
	{
		$this->entityService = $entityService;
//		$this->modelQueryPlanner = new ModelQueryPlanner($this->getRepository()->getModelService());
	}

	/**
	 * Retrieve nodes for a query
	 *
	 * @param Query $query
	 *
	 * @return Collection
	 * @throws Exception
	 * @throws \Exception
	 */
	public function query(Query $query): Collection
	{
		$qb = $this->getConnectionManager()->createQueryBuilder();

		$planner = new ModelQueryPlanner($this->getRepository()->getModelService());
		$plan = $planner->planModelQuery($query->getFrom(), $query);

		$this->configureSelect($qb, $plan, $query);
		$this->configureTables($qb, $plan, $query);
		$this->configureJoins($qb, $plan, $query);
		$this->configureFilterAssociationValues($qb, $plan, $query);
		$this->configureFilters($qb, $plan, $query);
		$this->configureSorts($qb, $plan, $query);

		if (null !== $query->getOffset()) $qb->setFirstResult($query->getOffset());
		if (null !== $query->getLimit()) $qb->setMaxResults($query->getLimit());
		if ($this->getRepository()->getLogger() !== null) $this->getRepository()->getLogger()->debug($qb->getSQL());

        $results = $qb->executeQuery()->fetchAllAssociative();

        return $plan->resultsToEntities($this->getREpository(), $this->getCOnnectionManager(), $results);
	}

	/**
	 * @return EntityService
	 */
	public function getEntityService(): EntityService
	{
		return $this->entityService;
	}

	/**
	 * @return RepositoryInterface
	 */
	public function getRepository(): RepositoryInterface
	{
		return $this->getEntityService()->getRepository();
	}

	/**
	 * @throws UnsupportedMultiColumnKeys
	 */
//	private function convertResultsToEntities(Query $query, TablePlan $tablePlan, array $results): Collection
//	{
//		$propertyLoader = new PropertyLoader($this->getRepository(), $this->getConnectionManager());
//		$entities       = new EntityCollection($this->entityService, $propertyLoader);
//
//		foreach($results as $result) {
//			$entity = $this->convertResultToEntity($this->getConnectionManager(), $query, $tablePlan, $result, $entities);
//			if (null === $entity) continue;
//
//			$entities[] = $entity;
//		}
//
//		return $entities;
//	}
//
//	/**
//	 * @throws UnsupportedMultiColumnKeys
//	 */
//	private function convertResultToEntity(Query $query, TablePlan $tablePlan, array $result, PropertyLoaderInterface $propertyLoader): ?LazyEntity
//	{
//		$modelName    = $tablePlan->getModelName();
//		$repository   = $this->getRepository();
//		$modelService = $repository->getModelService();
//
//		$entity = new LazyEntity($modelName, $this->getRepository(), $propertyLoader);
//		$entity->setIsNew(false);
//
//		$model = $modelService->getModel($modelName);
//		if (null === $model) return null;
//
//
//	}
////        foreach($tablePlan->getColumnPlans() as $columnPlan) {
////            $property = $this->getEntityService()->getResultHelper()->createPropertyFromData()
////        }
////        foreach($modelStack as $stackModel) {
////			$modelDef = $stackModel->getDef();
////			$propertyDefs = $modelDef->getProperties();
////
//////			foreach($propertyDefs as $propertyDef) {
//////				$propertyKey = $propertyDef->getName();
//////				$property    = $this->getEntityService()->getResultHelper()->createPropertyFromData($tablePlan, $propertyDef, $result);
//////
//////				$entity->addProperty($propertyKey, $property);
//////			}
////
////		}
//
//		return $entity;
//	}

//	private function getJoinedProperty(Query $query, ModelDefinition $modelDef, PropertyDefinition $propDef): ?QueryProperty
//	{
//		if (!$propDef->isVirtual() || !$propDef->hasReference()) return null;
//
//		foreach($query->getJoinProperties() as $joinProperty) {
//			if ($joinProperty->getModelName() === null || $joinProperty->getModelName() == $modelDef->getName()) {
//				return $joinProperty;
//			}
//		}
//
//		return null;
//	}

//	private function getKeywords(Query $query)
//	{
//		if (count($query->getKeywords()) > 0) throw new Exception(__METHOD__ . ' not yet implemented. ' . __FILE__ . ':' . __LINE__);
//
//		return;
//		/** @TODO Add keyword support */
//		$keywords = [];
//		/**
//		 * Generate keywords
//		 */
//		$filterKeywords = $this->createFiltersFromQuery($query);
//
//		$propertiesInfo = $this->getColumnsForProperties($query, $filterKeywords);
//
//		foreach($propertiesInfo as $propertyInfo) {
//
//			$tableKey = $propertyInfo['tableKey'];
//			$object = $propertyInfo['object'];
//			$fieldName = $object->getProperty();
//			$field_value = $object->getValue();
//
//			if (!isset($keywords[$tableKey])) $keywords[$tableKey] = array();
//			if (!isset($keywords[$tableKey][$fieldName])) $keywords[$tableKey][$fieldName] = array();
//
//			$keywords[$tableKey][$fieldName][] = $field_value;
//		}
//
//		return $keywords;
//	}
//	/**
//	 * Get filters for query
//	 * @param Query $query
//	 *
//	 * @return array
//	 */
//	private function createFiltersFromQuery(Query $query)
//	{
//		$filterKeywords = [];
//		$typeService = $this->getRepository()->getModelService();
//
//		foreach($query->getCompositeFilters() as $keyword) {
//			foreach($query->getFilterTypeQNames() as $typeQName) {
//				$nodeType = $typeService->getNodeTypeByTypeQName($typeQName);
//				foreach($nodeType->getDef()->getProperties() as $propertyDef) {
//					if ($propertyDef->isSearchable()) $filterKeywords[] = new Filter($typeQName, $propertyDef->getKey, $keyword, 'LIKE');
//				}
//			}
//		}
//
//		return $filterKeywords;
//	}

	/**
	 * @throws \Exception
	 */
	private function configureSelect(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
		foreach($tablePlan->getPropertyPlans() as $columnPlan) {
            $key = sprintf('%s.%s', $columnPlan->getModel(), $columnPlan->getProperty());

			// Add columns from MAIN table
			$columnPlan->buildSelectQuery($qb);

			if ($query->isJoinedProperty($columnPlan->getModel(), $columnPlan->getProperty())) {
				// Add columns from JOIN table
                if (!$tablePlan->hasPropertyReference($columnPlan->getProperty())) throw new RuntimeException('Property reference not found in table plan');
                $this->configureSelect($qb, $tablePlan->getPropertyReference($columnPlan->getProperty()), $query);
            }
		}
	}

	private function configureTables(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
		// Setup FROM table
		$tablePlan->buildSelectQuery($qb);
	}

	/**
     * Add JOIN tables for REFERENCED model records
	 * @param DbQueryBuilder $qb
	 * @param TablePlan $tablePlan
	 * @param Query $query
	 * @return void
	 */
	private function configureJoins(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
        foreach($query->getJoinProperties() as $joinProperty) {
			if (!$tablePlan->hasPropertyReference($joinProperty->getProperty())) continue;
			$tablePlan->getPropertyReference($joinProperty->getProperty())->buildSelectQuery($qb);
        }
	}

	private function configureFilterAssociationValues(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
//		$associationValues = $query->getFilterAssociationValues();
//
//		for ($i = 0, $j = count($associationValues); $i < $j; $i++) {
//			die(__FILE__.':'.__LINE__.PHP_EOL);
//			// Setup join for related tables
//			$tableKey = 'node_assocs';
//			$tableAlias = 'node_assocs_' . $i;
//
//			$joinKeys = sprintf('%s.src_node_uuid = n.node_uuid AND %s.src_node_version = n.node_version', $tableAlias, $tableAlias);
//
//			$qb->join('n', $tableKey, $tableAlias, $joinKeys);
//
//			// Setup where clause for association
//			$associationValue = $associationValues[$i]->getValue();
//			$associationTypeQName = $associationValues[$i]->getQName();
//
//			die(__FILE__.':'.__LINE__.PHP_EOL);
//			if (is_array($associationValue)) { // Search for multiple values using an array of possible vlaues
//
//				$valueSearchField = new DAOSearchFieldValues($tableAlias, 'tgt_node_uuid', $associationValue);
//				// Since we are search multiple values, we should make sure that only distince Nodes are returned (since a Node could potentially match more than one associations and create duplicate results
//				$search->makeDistinct(true);
//
//			} else {
//				$valueSearchField = new DAOSearchField($tableAlias, 'tgt_node_uuid', $associationValue);
//			}
//
//			$search->addSearchField($valueSearchField);
//
//			// Filter by association type qname, but only if a value is actually defined (otherwise it would be considered a "wildcard" search for any type of association with another node
//			if (!empty($associationTypeQName)) {
//
//				$search->addSearchField(new DAOSearchField($tableAlias, 'assocTypeQName', $associationTypeQName));
//
//			}
//		}
	}

	/**
	 * @param Filter $filter
	 * @param TablePlan $tablePlan
	 * @param Query $query
	 * @return string[]
	 */
	private function generateWhereConditionsForFilter(Filter $filter, TablePlan $tablePlan, Query $query): array
	{
		$whereConditions = [];
		$parameterValues = [];

		$modelName = $filter->getModelName() === null ? $query->getFrom() : $filter->getModelName();
        $modelDef = $this->getEntityService()->getRepository()->getModelService()->getModel($modelName)->getDef();

		if ($filter->getModelName() !== null && $filter->getModelName() != $query->getFrom()) {
			throw new RuntimeException('Querying another models filter values is not currently supported');
		}

        if (!$tablePlan->hasPropertyPlan($filter->getProperty())) throw new RuntimeException('Property ' . $filter->getProperty() . ' was not found in table plan');

        $columnPlan = $tablePlan->getPropertyPlan($filter->getProperty());
        $value = $filter->getValue();
//echo '<pre>';
//print_r($filter);
//echo '<hr/>';
//print_r($value);
//echo '<hr/>';
//print_r($columnPlan);
//die(__FILE__ . ':' . __LINE__ . PHP_EOL);
        foreach($columnPlan->getColumns() as $column) {
			$parameterKeyFormat = '%s';
			$useValues      = $this->normalizeParameterValues($modelDef->getProperty($filter->getProperty()), $value);

			switch ($filter->getOperator()) {
				case Filter::OPERATOR_LIKE:
					$operator = 'LIKE';
					break;

				case Filter::OPERATOR_NOT_EQUALS:
					$operator = 'NOT LIKE';
					break;

				case Filter::OPERATOR_IN:
				case Filter::OPERATOR_NOT_IN:
				case is_array($value):
				case $value instanceof Collection:
					$parameterKeyFormat = '(%s)';
					$operator           = ($filter->getOperator() == Filter::OPERATOR_NOT_IN ? 'NOT ' : '') . 'IN';

					// If not values are passed then we have to treat this IN / NOT IN request to intelligently work around this
					if (count($useValues) == 0) {
						if ($filter->getOperator() == Filter::OPERATOR_NOT_IN) {
							continue 2; // No point in using this exclusion since a zero value condition would theoretically match everything
						}
						// SQL cannot handle empty "column IN ()", so modify this filter to just ensure that nothing matches, i.e. i=0
						$tableAndColumn = '1';
						$operator       = '=';
						$useValues      = ['0'];
					}
					break;

				default:
					$operator = $filter->getOperator();

					break;
			}

			$valueCount   = count($useValues);
			$parameterKey = sprintf($parameterKeyFormat, implode(',', array_fill(0, $valueCount, '?')));

            if ($column instanceof Column) {
				$whereConditions[] = sprintf('`%s`.`%s` %s %s', $column->getTable(), $column->getColumn(), $operator, $parameterKey);
				foreach ($useValues as $useValue) {
					$parameterValues[] = $useValue;
				}
			} else {
                die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
            }
        }

		return [$whereConditions, $parameterValues];
	}

	/**
	 * @param CompositeFilter $compositeFilter
	 * @param TablePlan $tablePlan
	 * @param Query $query
	 * @return array
	 */
	private function generateCompositeFiltersWhereClause(CompositeFilter $compositeFilter, TablePlan $tablePlan, Query $query): array {
		$clauses = [];
		$parameters = [];

		foreach($compositeFilter->getFilters() as $filter) {
			if ($filter instanceof CompositeFilter) {
				/**
				 * string[] $subQuery
				 * mixed[] $subProps
				 */
				list($subClause, $subParams) = $this->generateCompositeFiltersWhereClause($filter, $tablePlan, $query);

				if (strlen($subClause) > 0) {
					$clauses[] = '(' . $subClause . ')';
					$parameters = array_merge($parameters, $subParams);
				}
			} else {
				list($whereConditions, $whereParameters) = $this->generateWhereConditionsForFilter($filter, $tablePlan, $query);
				foreach($whereConditions as $whereCondition) {
					$clauses[] = $whereCondition;
				}
				$parameters = array_merge($parameters, $whereParameters);
			}
		}

		return [implode(' ' . $compositeFilter->getType() . ' ', $clauses), $parameters];
	}

	private function configureFilters(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
		$clauses = [];
		$parameterValues = [];

		foreach($query->getCompositeFilters() as $compositeFilter) {
			list($subClause, $subParams) = $this->generateCompositeFiltersWhereClause($compositeFilter, $tablePlan, $query);
			$clauses[] = $subClause;
			$parameterValues = array_merge($parameterValues, $subParams);
		}

		if (count($clauses) == 0) return;

		$qb->where(implode(' AND ', $clauses));
		$qb->setParameters($parameterValues);
	}

	private function normalizeParameterValues(PropertyDefinition $propDef, $value)
	{
		$useValues = $value instanceof Collection ? clone $value : (is_array($value) ? $value : [$value]);

		/**
		 * Extract value from referenced entity if $value is defined as a EntityStub.
		 * @TODO Will it be necessary to support multi-valued keys, e.g. ['key1' => xyz, 'key2' => '123']?  Hopefully not, since even in multi-primary key models each property should have a simple value
		 */
		if ($propDef->isVirtual() && $propDef->hasReference()) {
			foreach($useValues as $ix => $useValue) {
				if ($useValue instanceof EntityStub) {
					$referencedModel = $this->entityService->getRepository()->getDictionaryService()->getModelDefinition($propDef->getReference()->getTargetModel());
					foreach ($referencedModel->getPrimaryKeys()->keys() as $primaryKey) {
						$useValues[$ix] = $useValue->getPropertyValue($primaryKey);
					}
				}
			}
		}

		return $useValues;
	}

	private function configureSorts(DbQueryBuilder $qb, TablePlan $tablePlan, Query $query)
	{
		foreach($query->getSorts() as $sort) {
            if ($sort->getModelName() !== null && $sort->getModelName() !== $query->getFrom()) throw new \RuntimeException('Sorting by another model is not currently supported');
            $propertyPlan = $tablePlan->getPropertyPlan($sort->getProperty());
            if ($propertyPlan instanceof PropertyPlan) {
				foreach ($propertyPlan->getColumns() as $column) {
					if ($column instanceof Column) {
						$qb->addOrderBy(sprintf('`%s`.`%s`', $column->getTable(), $column->getColumn()), $sort->getDirection());
					} else {
						die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
					}
				}
			} else {
                die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
            }
		}
	}

	/**
	 * Convenience method to return ConnectionManager
	 *
	 * @return ConnectionManager
	 */
	private function getConnectionManager(): ConnectionManager
	{
		return $this->entityService->getConnectionManager();
	}

	private function dumpQuery(DbQueryBuilder $qb)
	{
		?>
		<style>
			.sql-keyword { color: #0ad; font-weight: bold; }
		</style>
<?php
		$sql = $qb->getSQL();
		if (preg_match('/SELECT(.*?)FROM/', $sql, $matches)) {
			list($match, $columns) = $matches;
			$sql = str_replace($match, '<span class="sql-keyword">SELECT</span>' . $columns . '<br/><span class="sql-keyword">FROM</span>', $sql);
		}
		if (preg_match('/((LEFT )JOIN)/', $sql, $matches)) {
			list($match, $join) = $matches;
			$sql = str_replace($match, '<br/><span class="sql-keyword">' . $match . '</span>', $sql);
		}
		if (preg_match('/WHERE/', $sql, $matches)) {
			$sql = str_replace('WHERE', '<br/><span class="sql-keyword">WHERE</span>', $sql);
		}
		echo $sql . '<br/>' . PHP_EOL;
		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
	}
}
