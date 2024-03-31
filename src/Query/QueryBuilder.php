<?php

namespace WebImage\Models\Query;

use WebImage\Core\Collection;
use WebImage\Core\Dictionary;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Services\EntityServiceInterface;

class QueryBuilder
{
	/** @var Dictionary */
	private $aliases;
	/** @var Query */
	private $query;
	/** @var EntityServiceInterface */
	private $entityService;

//	->select('id', 'name') # Select only specific values for a property and ensure that any other properties do not get updated on SAVE
//	->joinProperty('raffle') # Eagerly JOIN raffle property and populate with all values
//	->populateProperty('raffle') # Eagerly JOIN raffle property and populate with all values
//	->pick('raffle.name', 'raffle_name') # JOIN raffle, but only select name and inject it as the virtual property "raffle_name"

	public function __construct(EntityServiceInterface $entityService)
	{
		$this->entityService = $entityService;
		$this->query         = new Query();
		$this->aliases       = new Dictionary();
	}

	/**
	 * @param string $from
	 */
	public function from(string $from)
	{
		$this->query->setFrom($from);

		return $this;
	}

	/**
	 * @param mixed|array|string $properties
	 *
	 * @return $this
	 */
	public function select($properties)
	{
		$properties = $this->normalizeProperties($properties);

		foreach($properties as $property) {
			$this->query->addProperty($property);
		}

		return $this;
	}

	/**
	 * @param mixed|array|string $properties
	 *
	 * @return $this
	 */
	public function join($properties)
	{
		$properties = $this->normalizeProperties($properties);

		foreach($properties as $property) {
			$this->query->addJoinProperty($property);
		}

		return $this;
	}

	/**
	 * @param string|int|array|null $id
	 * @return Entity|null
	 */
	public function get($id=null): ?Entity
	{
		$this->limit(1);
		if ($this->query->getFrom() === null) {
			throw new Exception('From must be set first');
		}

		$primaryKeys = $this->entityService->getRepository()->getDictionaryService()->getModel($this->query->getFrom())->getPrimaryKeys()->keys();

		if (is_string($id) || is_numeric($id)) {
			if (count($primaryKeys) != 1) {
				throw new \Exception('Cannot set $id to a string/number value if there is not exactly one primary key');
			}

			$this->where($primaryKeys[0], $id);
		} else if (is_array($id)) {
			$keys = array_keys($id);
			if (count(array_diff($keys, $primaryKeys)) > 0) {
				throw new \Exception(sprintf(
					'Only primary keys from %s[%s] can be used in %s (you specified %s)',
					$this->query->getFrom(),
					implode(', ', $primaryKeys),
					__METHOD__,
					'"' . implode('", "', $keys) . '"'
				));
			}
			foreach($id as $key => $val) {
				$this->where($key, $val);
			}
		} else if ($id !== null) {
			throw new \Exception('Unsupported type specified for $id');
		}

		$results = $this->execute();

		return count($results) > 0 ? $results[0] : null;
	}

	public function within(array $entities): self
	{
		if ($this->query->getFrom() === null) {
			throw new Exception('From must be set first');
		}

		$this->assertEntities($entities, $this->query->getFrom());

		$primaryKeys = $this->entityService->getRepository()->getDictionaryService()->getModel($this->query->getFrom())->getPrimaryKeys()->keys();

		$this->buildWhere(function(FilterBuilder $builder) use ($entities, $primaryKeys) {
//			$and = $builder->and(function())
//			WHERE (
//				(id = 1 AND version=1) OR
//				(id = 2 AND version=1)
//			)
			$builder->or(function(FilterBuilder $or) use ($entities, $primaryKeys) {

				foreach($entities as $entity) {
					$or->and(function(FilterBuilder $and) use ($primaryKeys, $entity) {
						foreach($primaryKeys as $primaryKey) {
							$and->eq($primaryKey, $entity[$primaryKey]);
						}
					});
				}
			});
		});

		return $this;
	}

	private function assertEntities(array $entities, string $requireModel=null)
	{
		foreach($entities as $entity) {
			$this->assertEntity($entity, $requireModel);
		}
	}

	private function assertEntity(EntityStub $entity, string $requireModel=null)
	{
		if ($requireModel !== null && $entity->getModel() != $requireModel) throw new \InvalidArgumentException('Expecting entity of time ' . $requireModel . ' but found ' . $entity->getModel());
	}

	/**
	 * Takes a comma-delimited list of properties, or an array of properties
	 * @param mixed|array|string $properties
	 *
	 * @return Property[]
	 */
	private function normalizeProperties($properties)
	{
		$properties = is_array($properties) ? $properties : [$properties];
		$return = [];

		foreach ($properties as $property) {
			$tProperties = preg_split('/, */', $property);
			foreach($tProperties as $sProperty) {
				$return[] = new Property($sProperty);
			}
		}

		return $return;
	}

	/**
	 * Sets the node types to SELECT
	 *
	 * @example from('App.Types.Person', 'p')
	 * @example from(['App.Types.Person' => 'p', 'App.Type.Contact' => 'c'])
	 *
	 * @param string|array $typeQNames
	 * @param string|null $alias
	 *
	 * @return $this
	 */
//	public function from($typeQNames, $alias = null)
//	{
//		$typeQNames = $this->normalizeFrom($typeQNames, $alias);
//
//		foreach ($typeQNames as $typeQName => $alias) {
//			$this->query->addTypeQNameFilter($typeQName);
//			if (null !== $alias) $this->aliases->set($alias, $typeQName);
//		}
//
//		return $this;
//	}

	public function resetWhere(): void
	{
		$this->query->resetFilters();
	}

	public function where($property, $value/*, $operator=Filter::OPERATOR_EQUALS*/): QueryBuilder
	{
		$this->buildWhere(function(FilterBuilder $filterBuilder) use ($property, $value/*, $operator*/) {
			$filterBuilder->eq($property, $value);
		});

		return $this;
	}

	/**
	 * @param callable(FilterBuilder):void $composer
	 * @return $this
	 */
	public function buildWhere(callable $composer): QueryBuilder
	{
		$composite = CompositeFilter::and();
		$builder = new FilterBuilder($composite);
		$composer($builder);
		if (count($composite->getFilters()) > 0) $this->query->addFilter($composite);

		return $this;
	}

//	/**
//	 * @param callable(@param string $test) $callable
//	 * @return QueryBuilder
//	 */
//	public function andWhere($property, $value=null, $operator=Filter::OPERATOR_EQUALS): QueryBuilder
//	{
//		$compositeWhere = CompositeFilter::and();
//		$composer = $this->createWhereComposer($property, $value, $operator);
//		$builder = new FilterBuilder($compositeWhere);
//		$composer($builder);
//
//		if (count($compositeWhere->getFilters()) > 0) $this->query->addFilter($compositeWhere);
//
//		return $this;
//	}
//
////	public function orWhere($property, $value=null, $operator=Filter::OPERATOR_EQUALS): QueryBuilder
//	public function orWhere(callable $composer): QueryBuilder
//	{
//		$compositeWhere = CompositeFilter::or();
////		$composer = $this->createWhereComposer($property, $value, $operator);
//		$builder = new FilterBuilder($compositeWhere);
//		$composer($builder);
//
//		if (count($compositeWhere->getFilters()) > 0) $this->query->addFilter($compositeWhere);
//
//		return $this;
//	}
//
//	private function createWhereComposer($property, $value=null, string $operator=null): callable
//	{
//		$builderCallback = function(FilterBuilder $builder) use ($property, $value, $operator) {
//			$builder->compare($property, $value, $operator);
//		};
//
//		return is_callable($property) ? $property : $builderCallback;
//	}

	public function sort(string $field, string $sortDirection=Query::SORT_ASC)
	{
		if (!in_array($sortDirection, [Query::SORT_ASC, Query::SORT_DESC])) throw new \InvalidArgumentException('Expecting ' . Query::SORT_ASC . ' or ' . Query::SORT_DESC . ' for sort');

		$this->query->addSort(new Sort($field, $sortDirection));

		return $this;
	}

	/**
	 * Set the current page number
	 * @param int $offset
	 */
	public function offset(int $offset)
	{
		$this->query->setOffset($offset);

		return $this;
	}

	/**
	 * Limit the number of results returned
	 * @param int $limit
	 */
	public function limit(int $limit)
	{
		$this->query->setLimit($limit);

		return $this;
	}

	/**
	 * @return Entity[]
	 */
	public function execute(): Collection
	{
		return $this->entityService->query($this->query);
	}

//	private function normalizeFrom($typeQNames, $alias=null)
//	{
//		if (is_array($typeQNames) && null !== $alias) {
//			throw new \InvalidArgumentException('$alias should be null when $typeQNames is specified as an array');
//		}
//		if (!is_array($typeQNames)) return [$typeQNames => $alias];
//
//		$return = [];
//
//		foreach($typeQNames as $ix => $typeQName) {
//			$qName = is_numeric($ix) ? $typeQName : $ix;
//			$alias = is_numeric($ix) ? null : $ix;
//			$return[$qName] = $alias;
//		}
//
//		return $return;
//	}
}
