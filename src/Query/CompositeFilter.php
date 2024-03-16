<?php

namespace WebImage\Models\Query;

use Countable;

class CompositeFilter implements Countable, FilterInterface
{
	const TYPE_AND = 'AND';
	const TYPE_OR  = 'OR';

	/** @var string AND or OR */
	private $type;
	/**
	 * @var FilterInterface[]
	 */
	private $filters = [];

	public function __construct(string $type)
	{
		$this->type = $type;
	}

	public function add(FilterInterface $filter)
	{
		$this->filters[] = $filter;
	}
	/**
	 * Returns a new CompositeExpression with the given parts added.
	 *
	 * @param self|string $filter
	 * @param self|string ...$filters
	 */
//	public function with($filter, ...$filters): self
//	{
//		$that = clone $this;
//
//		$that->filters = array_merge($that->filters, [$filter], $filters);
//
//		return $that;
//	}

	public function count()
	{
		return count($this->filters);
	}

	public static function and()
	{
		return new self(self::TYPE_AND);
	}

	public static function or()
	{
		return new self(self::TYPE_OR);
	}

	/**
	 * Retrieves the string representation of this composite expression.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if ($this->count() === 1) {
			return (string) $this->filters[0];
		}

		$separator = sprintf(') %s (', $this->type);

		return sprintf('(%s)', implode($separator, $this->filters));
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return Filter[]|CompositeFilter[]
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}
}
