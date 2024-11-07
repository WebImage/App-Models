<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class PropertyTablePlan extends TablePlan
{
	private string $propertyTable;
	private string $propertyTableAlias;

	public function __construct(string $modelName, string $tableName, string $tableAlias, string $propertyTable, string $propertyTableAlias)
	{
		parent::__construct($modelName, $tableName, $tableAlias);

		$this->propertyTable = $propertyTable;
		$this->propertyTableAlias = $propertyTableAlias;
	}

	public function getPropertyTable(): string
	{
		return $this->propertyTable;
	}

	public function getPropertyTableAlias(): string
	{
		return $this->propertyTableAlias;
	}
}