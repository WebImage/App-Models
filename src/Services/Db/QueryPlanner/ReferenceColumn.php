<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class ReferenceColumn extends Column
{
	private string $property;
	public function __construct(string $table, string $column, string $alias, string $property, ?string $subKey = null)
	{
		parent::__construct($table, $column, $alias, $subKey);
		$this->property = $property;
	}
}