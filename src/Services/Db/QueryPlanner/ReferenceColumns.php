<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Core\ArrayHelper;

class ReferenceColumns implements SelectQueryBuilderInterface
{
	/** @var ReferenceColumn[] */
	private array $columns;

	/**
	 * @param ReferenceColumn[] $columns
	 */
	public function __construct(array $columns)
	{
		ArrayHelper::assertItemTypes($columns, ReferenceColumn::class);
		$this->columns = $columns;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
	}
}