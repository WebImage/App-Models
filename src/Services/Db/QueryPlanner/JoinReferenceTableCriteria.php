<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class JoinReferenceTableCriteria
{
	private string $localTable;
	private string $localColumn;
	private string $foreignColumn;
	private string $foreignTable;

	/**
	 * @param string $localTable
	 * @param string $localColumn
	 * @param string $foreignTable
	 * @param string $foreignColumn
	 */
	public function __construct(string $localTable, string $localColumn, string $foreignTable, string $foreignColumn)
	{
		$this->localTable = $localTable;
		$this->localColumn   = $localColumn;
		$this->foreignTable = $foreignTable;
		$this->foreignColumn = $foreignColumn;
	}

	public function getLocalTable(): string
	{
		return $this->localTable;
	}

	public function getLocalColumn(): string
	{
		return $this->localColumn;
	}

	public function getForeignTable(): string
	{
		return $this->foreignTable;
	}

	public function getForeignColumn(): string
	{
		return $this->foreignColumn;
	}
}