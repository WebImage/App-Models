<?php

namespace WebImage\Models\Query;

use WebImage\Models\Query\CompositeFilter;

class FilterBuilder
{
	/**
	 * @var CompositeFilter
	 */
	private $compositeFilter;

	public function __construct(CompositeFilter $compositeFilter=null)
	{
		$this->compositeFilter = $compositeFilter ?: new CompositeFilter();
	}

	public function and(callable $callable)
	{
		$compositeFilter = CompositeFilter::and();
		$builder = new FilterBuilder($compositeFilter);
		call_user_func($callable, $builder);

		if (count($compositeFilter->getFilters()) > 0) {
			$this->compositeFilter->add($compositeFilter);
		}

		return $this;
	}

	public function or(callable $callable)
	{
		$compositeFilter = CompositeFilter::or();
		$builder = new FilterBuilder($compositeFilter);
		call_user_func($callable, $builder);

		if (count($compositeFilter->getFilters()) > 0) {
			$this->compositeFilter->add($compositeFilter);
		}

		return $this;
	}

	public function compare($property, $value, $operator): FilterBuilder
	{
		$this->compositeFilter->add(new Filter($property, $value, $operator));

		return $this;
	}

	public function eq($property, $value)
	{
		return $this->compare($property, $value, Filter::OPERATOR_EQUALS);
	}

	public function neq($property, $value)
	{
		return $this->compare($property, $value, Filter::OPERATOR_NOT_EQUALS);
	}

	public function gt($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_GT);
	}

	public function gte($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_GTE);
	}

	public function lt($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_LT);
	}

	public function lte($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_LTE);
	}

	public function in($property, $values): FilterBuilder
	{
		return $this->compare($property, $values, Filter::OPERATOR_IN);
	}

	public function notIn($property, $values): FilterBuilder
	{
		return $this->compare($property, $values, Filter::OPERATOR_NOT_IN);
	}

	public function like($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_LIKE);
	}

	public function notLike($property, $value): FilterBuilder
	{
		return $this->compare($property, $value, Filter::OPERATOR_NOT_LIKE);
	}

	public function isNull($property): FilterBuilder
	{
		return $this->compare($property, null, Filter::OPERATOR_IS_NULL);
	}
}
