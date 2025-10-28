<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class MultiValuePropertyPlan implements SelectQueryBuilderInterface
{
	private string $model;
	private string $property;
	private TablePlan $propertyTablePlan;

	/**
	 * @param string $model
	 * @param string $property
	 * @param TablePlan $propertyTablePlan
	 */
	public function __construct(string $model, string $property, TablePlan $propertyTablePlan)
	{
		$this->model             = $model;
		$this->property          = $property;
		$this->propertyTablePlan = $propertyTablePlan;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
	}
}