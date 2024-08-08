<?php

namespace WebImage\Models\Services\Db;

use RuntimeException;
use WebImage\Core\Collection;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Query\CompositeFilter;
use WebImage\Models\Query\Filter;
use WebImage\Models\Query\Property As QueryProperty;
use WebImage\Db\QueryBuilder As DbQueryBuilder;

use WebImage\Db\ConnectionManager;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Query\Query;
use WebImage\Models\Services\Db\EntityService;
use WebImage\Models\Services\RepositoryInterface;

class EntityQueryService
{
	/** @var \WebImage\Models\Services\Db\EntityService */
	private $entityService;

	public function __construct(EntityService $entityService)
	{
		$this->entityService = $entityService;
	}

	/**
	 * Retrieve nodes for a query
	 *
	 * @param Query $query
	 *
	 * @return EntityStub[]
	 */
	public function query(Query $query): Collection
	{
		$qb = $this->getConnectionManager()->createQueryBuilder();

		$modelService = $this->getRepository()->getModelService();
//		$rootTableKey = TableNameHelper::getRootTableName($modelService, $query->getFrom());

		$this->configureSelect($qb, $query);
		$this->configureTables($qb, $query);
		$this->configureJoins($qb, $query);
		$this->configureFilterAssociationValues($qb, $query);
		$this->configureFilters($qb, $query);
//		$this->configureKeywords($qb, $query);
//		$this->configureFilterTypeQNames($qb, $query, $rootTableKey);
		$this->configureSorts($qb, $query);
//		$this->configureStatus($qb, $query, $rootTableKey);

		if (null !== $query->getOffset()) $qb->setFirstResult($query->getOffset());
		if (null !== $query->getLimit()) $qb->setMaxResults($query->getLimit());
		if ($this->getRepository()->getLogger() !== null) $this->getRepository()->getLogger()->debug($qb->getSQL());
		$results = $qb->executeQuery()->fetchAllAssociative();

		return $this->convertResultsToEntities($query, $results/*, $rootTableKey*/);
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

	private function convertResultsToEntities(Query $query, array $results/*, string $rootTableKey*/): Collection
	{
		$propertyLoader = new PropertyLoader($this->getRepository());
		$entities       = new EntityCollection($this->entityService, $propertyLoader);

		foreach($results as $result) {
			$entity = $this->convertResultToEntity($query, $result, $entities/*, $rootTableKey*/);
			if (null === $entity) continue;

			$entities[] = $entity;
		}

		return $entities;
	}

	private function convertResultToEntity(Query $query, array $result, PropertyLoaderInterface $propertyLoader/*, string $rootTableKey*/): ?LazyEntity
	{
		$modelName       = $query->getFrom();
		$repository      = $this->getRepository();
		$modelService    = $repository->getModelService();

		$entity = new LazyEntity($modelName, $this->getRepository(), $propertyLoader);
		$entity->setIsNew(false);

		$model = $modelService->getModel($modelName);

		if (null === $model) return null;

		$modelStack = $model->getModelStack();
		foreach($modelStack as $stackModel) {

			$modelDef = $stackModel->getDef();

			$propertyDefs = $modelDef->getProperties();
			$propertyTableKey = TableNameHelper::getTableNameFromDef($stackModel->getDef());

			foreach($propertyDefs as $propertyDef) {
				$propertyKey = $propertyDef->getName();
				$property    = $this->getEntityService()->getResultHelper()->createPropertyFromData($propertyTableKey, $propertyDef, $result);

				$entity->addProperty($propertyKey, $property);
			}
		}

		return $entity;
	}

	private function getJoinedProperty(Query $query, ModelDefinition $modelDef, PropertyDefinition $propDef): ?QueryProperty
	{
		if (!$propDef->isVirtual() || !$propDef->hasReference()) return null;

		foreach($query->getJoinProperties() as $joinProperty) {
			if ($joinProperty->getModelName() === null || $joinProperty->getModelName() == $modelDef->getName()) {
				return $joinProperty;
			}
		}

		return null;
	}

	private function getKeywords(Query $query)
	{
		if (count($query->getKeywords()) > 0) throw new Exception(__METHOD__ . ' not yet implemented. ' . __FILE__ . ':' . __LINE__);

		return;
		/** @TODO Add keyword support */
		$keywords = [];
		/**
		 * Generate keywords
		 */
		$filterKeywords = $this->createFiltersFromQuery($query);

		$propertiesInfo = $this->getColumnsForProperties($query, $filterKeywords);

		foreach($propertiesInfo as $propertyInfo) {

			$tableKey = $propertyInfo['tableKey'];
			$object = $propertyInfo['object'];
			$fieldName = $object->getProperty();
			$field_value = $object->getValue();

			if (!isset($keywords[$tableKey])) $keywords[$tableKey] = array();
			if (!isset($keywords[$tableKey][$fieldName])) $keywords[$tableKey][$fieldName] = array();

			$keywords[$tableKey][$fieldName][] = $field_value;
		}

		return $keywords;
	}
	/**
	 * Get filters for query
	 * @param Query $query
	 *
	 * @return array
	 */
	private function createFiltersFromQuery(Query $query)
	{
		$filterKeywords = [];
		$typeService = $this->getRepository()->getModelService();

		foreach($query->getCompositeFilters() as $keyword) {
			foreach($query->getFilterTypeQNames() as $typeQName) {
				$nodeType = $typeService->getNodeTypeByTypeQName($typeQName);
				foreach($nodeType->getDef()->getProperties() as $propertyDef) {
					if ($propertyDef->isSearchable()) $filterKeywords[] = new Filter($typeQName, $propertyDef->getKey, $keyword, 'LIKE');
				}
			}
		}

		return $filterKeywords;
	}

	private function configureSelect(DbQueryBuilder $qb, Query $query)
	{
		/**
		 * Add fields
		 */
		$propertiesInfo = $this->getColumnsForProperties($query, $query->getProperties());
		if (count($propertiesInfo) > 0) throw new RuntimeException('Selecting specific columns is not currently supported');

		/**
		 * Add all fields from all tables
		 */
		$typeService     = $this->getRepository()->getModelService();

		// Add all columns to results
		$model = $typeService->getModel($query->getFrom());
		$tableKey = TableNameHelper::getTableNameFromDef($model->getDef());

		foreach($model->getDef()->getProperties() as $propDef) {
			if ($propDef->isMultiValued() || ($propDef->isVirtual() && !$propDef->hasReference())) continue;

			$propertyColumns = TableHelper::getPropertyColumns($this->getRepository()->getModelService(), $model->getDef(), $propDef->getName());

			foreach($propertyColumns->getColumns() as $tableColumn) {
				$column = TableNameHelper::getColumnName($tableKey, $tableColumn->getName(), $tableColumn->getDataTypeField()->getKey());
				$alias = TableNameHelper::getColumnNameAlias($tableKey, $tableColumn->getName(), $tableColumn->getDataTypeField()->getKey());
				$qb->addSelect(sprintf('%s AS %s', $column, $alias));
			}
		}

//		foreach($query->getFilterTypeQNames() as $typeQName) {
//
//			$type = $typeService->getNodeTypeByTypeQName($typeQName);
//			if ($type === null) throw new RuntimeException(sprintf('Unknown type for filter type: %s', $typeQName));
//
//			foreach($type->getTypeStack() as $type) {
//				$tableKey = TableNameHelper::getTableKeyFromDef($type->getDef());
//				if (!TableNameHelper::shouldDefHavePhysicalTable($type->getDef())) continue;
//
//				foreach($type->getDef()->getProperties() as $property) {
//					if ($property->isMultiValued()) continue; // Multi-valued properties are handled elsewhere
//
//					$dataType = $dataTypeService->getDataType($property->getDataType());
//
//					foreach($dataType->getModelFields() as $field) {
//						$column = TableNameHelper::getColumnName($tableKey, $property->getKey(), $field->getKey());
//						$alias = TableNameHelper::getColumnNameAlias($tableKey, $property->getKey(), $field->getKey());
//
//						if (!in_array($alias, $uniqueColumns)) {
//							$uniqueColumns[] = $alias;
//							$qb->addSelect(sprintf('%s AS %s', $column, $alias));
//						}
//					}
//				}
//			}
//		}
	}

	private function configureTables(DbQueryBuilder $qb, Query $query)
	{
		// Setup from table
		$rootTableKey = TableNameHelper::getRootTableName($this->getRepository()->getModelService(), $query->getFrom());
		$rootTableName = $rootTableKey;

		$qb->from($rootTableName, $rootTableKey);
	}

	private function configureJoins(DbQueryBuilder $qb, Query $query)
	{
		$modelService   = $this->getRepository()->getModelService();
		$joinProperties = $query->getJoinProperties();
		$model          = $modelService->getModel($query->getFrom());
		$modelTable     = TableNameHelper::getTableNameFromDef($model->getDef());

		foreach ($joinProperties as $joinProperty) {
			if ($joinProperty->getModelName() !== null) throw new RuntimeException('Configure joins with a specific model are not yet supported'); // @TODO Allow selecting joins from joined table?

			$joinPropertyDef = $model->getDef()->getProperty($joinProperty->getProperty());

			if ($joinPropertyDef === null) throw new \InvalidArgumentException(sprintf('Model %s does not have joinable field %s.', $query->getFrom(), $joinProperty->getProperty()));
			else if (!$joinPropertyDef->hasReference()) throw new \InvalidArgumentException(sprintf('Model %s.%s cannot be joined because it does not have a reference.', $query->getFrom(), $joinProperty->getProperty()));
			else if ($joinPropertyDef->isMultiValued()) continue; // Cannot do a simple join on multi-valued properties

			$joinPropertyColumns = TableHelper::getPropertyColumns($modelService, $model->getDef(), $joinProperty->getProperty());
			$refModelName        = $joinPropertyDef->getReference()->getTargetModel();
			$refModel            = $modelService->getModel($refModelName);
			if ($refModel === null) throw new RuntimeException(sprintf('Model %s.%s references %s, but %s does not exist.', $query->getFrom(), $joinProperty->getProperty(), $refModelName, $refModelName));

			$propertyJoinCriteria = [];
			$propTableAlias       = TableNameHelper::getColumnNameAlias($joinProperty->getProperty(), $joinPropertyColumns->getReferencedTable());

			foreach ($joinPropertyColumns->getColumns() as $column) {
				$propertyJoinCriteria[] = $propTableAlias . '.' . $column->getReferencedColumnName() . ' = ' . $column->getTableName() . '.' . $column->getName();
			}

			$qb->innerJoin($modelTable, $joinPropertyColumns->getReferencedTable(), $propTableAlias, implode(' AND ', $propertyJoinCriteria));

			foreach (TableHelper::getPropertiesColumns($modelService, $refModel->getDef())->getProperties() as $propertiesColumn) {
				foreach ($propertiesColumn->getColumns() as $column) {
					$columnName  = TableNameHelper::getColumnName($propTableAlias, $column->getName(), $column->getDataTypeField()->getKey());
					$columnAlias = TableNameHelper::getColumnNameAlias($propTableAlias, $column->getName(), $column->getDataTypeField()->getKey());
					$qb->addSelect(sprintf('%s AS %s', $columnName, $columnAlias));
				}
			}
		}
	}

	private function configureFilterAssociationValues(DbQueryBuilder $qb, Query $query)
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
	 * @param Query $query
	 * @return string[]
	 */
	private function generateWhereConditionsForFilter(Filter $filter, Query $query): array {
		$whereConditions = [];
		$modelName = $filter->getModelName() === null ? $query->getFrom() : $filter->getModelName();

		$propDef = $this->getRepository()->getDictionaryService()->getModelDefinition($modelName)->getProperty($filter->getProperty());

		if ($propDef === null) throw new \InvalidArgumentException('Invalid property in query: ' . $filter->getProperty());
		else if ($propDef->isMultiValued()) return [];

		$propertyColumns = $this->getColumnsForProperty($modelName, $filter);

		foreach ($propertyColumns->getProperties() as $property => $tableColumns) {

			$value = $filter->getValue();

			foreach ($tableColumns->getColumns() as $tableColumn) {
				$parameterKeyFormat = '%s';

				$useValues      = $this->normalizeParamterValues($propDef, $value);
				$tableAndColumn = sprintf('%s.`%s`', $tableColumns->getTable(), $tableColumn->getName());

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

				$count        = count($useValues);
				$parameterKey = sprintf($parameterKeyFormat, implode(',', array_fill(0, $count, '?')));

				$whereConditions[] = sprintf('%s %s %s', $tableAndColumn, $operator, $parameterKey);
				foreach ($useValues as $useValue) {
					$parameterValues[] = $useValue;
				}
			}
		}

		return [$whereConditions, $parameterValues];
	}
	/**
	 * @param CompositeFilter $compositeFilter
	 */
	private function generateCompositeFiltersWhereClause(CompositeFilter $compositeFilter, Query $query): array {
		$clauses = [];
		$parameters = [];

		foreach($compositeFilter->getFilters() as $filter) {
			if ($filter instanceof CompositeFilter) {
				/**
				 * string[] $subQuery
				 * mixed[] $subProps
				 */
				list($subClause, $subParams) = $this->generateCompositeFiltersWhereClause($filter, $query);

				if (strlen($subClause) > 0) {
					$clauses[] = '(' . $subClause . ')';
					$parameters = array_merge($parameters, $subParams);
				}
			} else {
				list($whereConditions, $whereParameters) = $this->generateWhereConditionsForFilter($filter, $query);
				foreach($whereConditions as $whereCondition) {
					$clauses[] = $whereCondition;
				}
				$parameters = array_merge($parameters, $whereParameters);
			}
		}

		return [implode(' ' . $compositeFilter->getType() . ' ', $clauses), $parameters];
	}

	private function configureFilters(DbQueryBuilder $qb, Query $query)
	{
		$clauses = [];
		$parameterValues = [];

		foreach($query->getCompositeFilters() as $compositeFilter) {
			list($subClause, $subParams) = $this->generateCompositeFiltersWhereClause($compositeFilter, $query);
			$clauses[] = $subClause;
			$parameterValues = array_merge($parameterValues, $subParams);
		}

		if (count($clauses) == 0) return;

		$qb->where(implode(' AND ', $clauses));
		$qb->setParameters($parameterValues);
	}

	private function normalizeParamterValues(PropertyDefinition $propDef, $value)
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

//	private function configureKeywords(DbQueryBuilder $qb, Query $query)
//	{
//		$keywords = $this->getKeywords($query);
//
//		// Add keywords to search
//		if (count($keywords) > 0) {
//			throw new \RuntimeException('Keywords do not yet work');
//			// Build list of searchable fields
//			$keyword_group = new DAOSearchOrGroup();
//
//			foreach($keywords as $tableKey => $fields) {
//				foreach($fields as $name => $values) {
//					foreach($values as $value) {
//						$keyword_group->addSearchField(new DAOSearchFieldWildcard($tableKey, $name, $value));
//					}
//				}
//			}
//
//			$search->addSearchField($keyword_group);
//		}
//	}

//	private function configureFilterTypeQNames(DbQueryBuilder $qb, Query $query, string $rootTableKey)
//	{
//		// Filter search by type_names (e.g. {WebImage.Node.Types.Content)
//		if (count($query->getFilterTypeQNames()) > 0) {
//			$typeQNameColumn = TableNameHelper::getColumnName($rootTableKey, 'type_qname');
//
//			// @TODO Auto-include children of Type QNames
//			$qb->andWhere($typeQNameColumn . ' IN (:type_qname)');
//			$qb->setParameter('type_qname', $query->getFilterTypeQNames(), Connection::PARAM_STR_ARRAY);
//		}
//	}

	private function configureSorts(DbQueryBuilder $qb, Query $query)
	{
		foreach($query->getSorts() as $sort) {
			$modelName = $sort->getModelName() === null ? $query->getFrom() : $sort->getModelName();
			$propertyColumns = $this->getColumnsForProperty($modelName, $sort);

			foreach($propertyColumns->getColumns() as $column) {
				$qb->addOrderBy(sprintf('`%s`.`%s`', $column->getTableName(), $column->getName()), $sort->getDirection());
			}
		}
	}

//	private function configureStatus(DbQueryBuilder $qb, Query $query, string $rootTableKey)
//	{
//		$qb->andWhere($rootTableKey . ' .status = :node_status');
//		$qb->setParameter('node_status', NodeService::NODE_STATUS_ACTIVE);
//	}

	/**
	 * Get the property definitions for the supplied objects
	 *
	 * @param Query $query
	 * @param QueryProperty[]|Filter[] $properties
	 *
	 * @return ModelPropertyTableColumns[] The columns used for a table query
	 */
	private function getColumnsForProperties(Query $query, array $properties)
	{
		$columns = array();

		/** @var QueryProperty $property */

		foreach($properties as $propertyColumns) {
			// @TODO Modify to check if each $propertyColumns includes model?
			foreach($this->getColumnsForProperty($query->getFrom(), $propertyColumns) as $propertyColumn) {
				$columns[] = $propertyColumn;
			}
		}

		return $columns;
	}

	/**
	 * @param string $modelName
	 * @param QueryProperty $property
	 *
	 * @return ModelPropertyTableColumns[]
	 */
	private function getColumnsForProperty(string $modelName, QueryProperty $property): PropertiesColumns
	{
		$repository      = $this->getRepository();
		$typeService     = $repository->getModelService();

		return TableHelper::getPropertiesColumns($typeService, $typeService->getModel($modelName)->getDef(), $property->getProperty());
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
}
