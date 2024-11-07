<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Core\ArrayHelper;

class CompoundColumn implements SelectQueryBuilderInterface
{
	private array $columns;

	/**
	 * @param PropertyPlan[] $columns
	 */
	public function __construct(array $columns)
	{
		ArrayHelper::assertItemTypes($columns, PropertyPlan::class);
		$this->columns = $columns;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
		foreach($this->columns as $column) {
			$column->buildSelectQuery($builder);
		}
	}
}