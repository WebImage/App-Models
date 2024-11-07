<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

class ReferenceTablePlan extends TablePlan
{
	private string $sourceTableAlias;

	public function __construct(string $modelName, string $tableName, string $tableAlias, string $sourceTableAlias)
	{
		parent::__construct($modelName, $tableName, $tableAlias);
		$this->sourceTableAlias = $sourceTableAlias;
	}

	/**
	 * @return string
	 */
	public function getSourceTableAlias(): string
	{
		return $this->sourceTableAlias;
	}

	/**
	 * @var JoinReferenceTableCriteria[]
	 */
	private array $joinCriteria = [];

	public function addJoinCriteria(JoinReferenceTableCriteria $criteria) {
		$this->joinCriteria[] = $criteria;
	}

	public function getJoinCriteria(): array
	{
		return $this->joinCriteria;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
		/**
		 * @TODO It might be better to set this up as sub-query to LIMIT 1, e.g. LEFT JOIN (SELECT .... LIMIT 1) AS alias ON (alias.id = table.id)
		 */
		$criteria = array_map(function(JoinReferenceTableCriteria $criterion) {
			return sprintf('`%s`.`%s` = `%s`.`%s`', $criterion->getLocalTable(), $criterion->getLocalColumn(), $criterion->getForeignTable(), $criterion->getForeignColumn());
		}, $this->getJoinCriteria());

		// Add properties to SELECT
		foreach($this->getPropertyPlans() as $columnPlan) {
			$columnPlan->buildSelectQuery($builder);
		}

		$builder->leftJoin($this->getSourceTableAlias(), $this->getTableName(), $this->getTableAlias(), implode(' AND ', $criteria));
	}
}