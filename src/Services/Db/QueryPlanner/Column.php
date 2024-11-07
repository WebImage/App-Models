<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class Column
{
	private string  $table;
	private string  $column;
	private string  $alias;
	private ?string $subKey = null;

	/**
	 * @param string $table
	 * @param string $column
	 * @param string $alias
	 * @param string|null $subKey
	 * @throws \Exception
	 */
	public function __construct(string $table, string $column, string $alias, ?string $subKey = null)
	{
		$this->table  = $table;
		$this->column = $column;
		$this->alias  = $alias;
		$this->subKey = $subKey;
	}

	public function getTable(): string
	{
		return $this->table;
	}

	public function getColumn(): string
	{
		return $this->column;
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function getSubKey(): ?string
	{
		return $this->subKey;
	}
}