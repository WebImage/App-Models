<?php

namespace WebImage\Models\Query;

class Query
{
	const SORT_ASC = 'ASC';
	const SORT_DESC = 'DESC';

	/**
	 * @var string
	 */
	private $from;
	/**
	 * @var array
	 */
	private $properties = [];
	/**
	 * @var Property[]
	 */
	private $joinProperties = [];
	/**
	 * @var CompositeFilter[]
	 */
	private $compositeFilters = [];
	/**
	 * @var array
	 */
	private $sorts = [];
	/**
	 * @var array
	 */
	private $filterAssociationValues = [];
	/**
	 * @var string[]
	 */
//	private $keywords = [];
	/**
	 * @var int|null
	 */
	private $offset;
	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @return string
	 */
	public function getFrom(): ?string
	{
		return $this->from;
	}

	/**
	 * @param string $from
	 */
	public function setFrom(string $from): void
	{
		$this->from = $from;
	}

	/**
	 * Get query properties
	 *
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * Get query properties
	 *
	 * @return array
	 */
	public function getJoinProperties(): array
	{
		return $this->joinProperties;
	}

	/**
	 * Get the query filters
	 *
	 * @return CompositeFilter[]
	 */
	public function getCompositeFilters(): array
	{
		return $this->compositeFilters;
	}

	/**
	 * Reset the filters
	 */
	public function resetFilters(): void
	{
		$this->compositeFilters = [];
	}

	/**
	 * Get the query sorts
	 *
	 * @return Sort[]
	 */
	public function getSorts(): array
	{
		return $this->sorts;
	}

	/**
	 * Get the query filter association values
	 *
	 * @return array
	 */
	public function getFilterAssociationValues(): array
	{
		return $this->filterAssociationValues;
	}

	/**
	 * Get the query keywords
	 *
	 * @return array
	 */
//	public function getKeywords(): array
//	{
//		return $this->keywords;
//	}

	/**
	 * Get the current offset
	 *
	 * @return int|null
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Get the max results to retrieve at a time
	 *
	 * @return int
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Set the current offset
	 *
	 * @param int $offset
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;
	}

	/**
	 * Set the results per page
	 *
	 * @param int $limit
	 */
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}

	/**
	 * Add a property that needs to be added to the query results.
	 *
	 * There are two ways to construct the addField method...
	 * 1. $query->addField($type_qname, $field)
	 * 2. $query->addField($field_name) <-- If the second value is omitted then we'll assume this is the method we want
	 */
	public function addProperty(Property $property)
	{
		$this->properties[] = $property;
	}
	/**
	 * Add a joined property that needs to be added to the query results.
	 */
	public function addJoinProperty(Property $property)
	{
		$this->joinProperties[] = $property;
	}

	/**
	 * Add a filter that will be used to filter the query results
	 *
	 * @param CompositeFilter $compositeFilter
	 */
	public function addFilter(CompositeFilter $compositeFilter)
	{
		$this->compositeFilters[] = $compositeFilter;
	}

	/**
	 * Add a sorting field that will be used to filter the query results
	 *
	 * @param Sort $sort
	 */
	public function addSort(Sort $sort)
	{
		$this->sorts[] = $sort;
	}

	/**
	 * Add a keyword to the query
	 *
	 * @param string $keyword
	 */
//	public function addKeyword($keyword)
//	{
//		$this->keywords[] = $keyword;
//	}

	/**
	 * Add a type qname filter
	 *
	 * @param $typeQName
	 */
//	public function addTypeQNameFilter($typeQName)
//	{
//		$this->filterTypeQNames[] = $typeQName;
//	}

	/**
	 * Add a filter to check if that a Node has an association with another Node
	 *
	 * There are two ways to construct the addAssociationValueFilter method...
	 * 1. $query->addAssociationValueFilter($association_type_qname, $value)
	 * 2. $query->addAssociationValueFilter($value) <!-- assumed if the second parameter is left blank
	 */
	public function addAssociationValueFilter($associationTypeQName, $value = null)
	{
		// If $value is null, then shift values over and assume second version of method
		if (null === $value) {
			$value = $associationTypeQName;
			$associationTypeQName = null;
		}
		$this->filterAssociationValues[] = new AssociationValue($associationTypeQName, $value);
	}

//	/**
//	 * Setup the current page and results per page
//	 *
//	 * @param int $currentPage
//	 * @param int $resultsPerPage
//	 */
//	public function paginate($currentPage, $resultsPerPage)
//	{
//		$this->setCurrentPage($currentPage);
//		$this->setResultsPerPage($resultsPerPage);
//	}
}
