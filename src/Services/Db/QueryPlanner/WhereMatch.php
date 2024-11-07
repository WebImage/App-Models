<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class WhereMatch implements WhereMatchInterface
{
	private string $table;
	private string $column;
	private string $entityProperty;

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $entityProperty
	 */
	public function __construct(string $table, string $column, string $entityProperty)
	{
		$this->table          = $table;
		$this->column         = $column;
		$this->entityProperty = $entityProperty;
	}

	public function build()
	{
		$this->table;
		$this->column;
		$this->entityProperty;
		// TODO: Implement build() method.
	}
}