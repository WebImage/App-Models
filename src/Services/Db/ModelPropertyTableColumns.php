<?php

namespace WebImage\Models\Services\Db;

/**
 * A properties associated table and column names
 * Class TableColumns
 * @package WebImage\Models\Services\Db
 */
class ModelPropertyTableColumns
{
	/** @var string The model that these columns apply to */
	private $model;
	/** @var string */
	private $table;
	/** @var ?string The model being referenced */
	private $refModel;
	/** @var ?string The model property being referenced */
	private $refProperty;
	/** @var ?string THe table being referenced */
	private $refTable;
	/** @var TableColumn[] */
	private $columns = [];

	/**
	 * TableColumns constructor.
	 * @param string $model
	 * @param string $property
	 * @param string $table
	 */
	public function __construct(string $model, string $table)
	{
		$this->model    = $model;
		$this->table    = $table;
	}

	/**
	 * @return string
	 */
	public function getModel(): string
	{
		return $this->model;
	}

	/**
	 * @param string $model
	 */
	public function setModel(string $model): void
	{
		$this->model = $model;
	}

	/**
	 * @return string
	 */
	public function getTable(): string
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 */
	public function setTable(string $table): void
	{
		$this->table = $table;
	}

	/**
	 * @return string|null
	 */
	public function getReferencedModel(): ?string
	{
		return $this->refModel;
	}

	/**
	 * @param string|null $refModel
	 */
	public function setReferencedModel(?string $refModel): void
	{
		$this->refModel = $refModel;
	}

	public function getReferencedProperty(): ?string
	{
		return $this->refProperty;
	}

	public function setReferencedProperty(?string $property): void
	{
		$this->refProperty = $property;
	}

	/**
	 * @return string|null
	 */
	public function getReferencedTable(): ?string
	{
		return $this->refTable;
	}

	/**
	 * @param string|null $refTable
	 */
	public function setReferencedTable(?string $refTable): void
	{
		$this->refTable = $refTable;
	}

	/**
	 * @param TableColumn $column
	 */
	public function addColumn(TableColumn $column): void
	{
		$this->columns[] = $column;
	}

	/**
	 * @return TableColumn[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}
}
