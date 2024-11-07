<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class WhereMatchKey extends WhereMatch
{
	private string $key;

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $entityProperty
	 * @param string $key
	 */
	public function __construct(string $table, string $column, string $entityProperty, string $key)
	{
		parent::__construct($table, $column, $entityProperty);
		$this->key = $key;
	}

	public function getKey(): string
	{
		return $this->key;
	}
}