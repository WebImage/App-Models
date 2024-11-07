<?php

namespace WebImage\Models\Services\Db;

use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\DataTypeField;

class TableColumn
{
	/** @var string */
	private string $table;
	/** @var string */
	private string $name;
	/** @var ?string */
	private ?string $referencedColumnName;
	/** @var DataTypeField */
	private $dataTypeField;

	/**
	 * PropertyTableColumn constructor.
	 * @param string $tableName
	 * @param string $name
	 * @param DataTypeField $dataTypeField
	 * @param string|null $referencedColumnName
	 */
	public function __construct(string $tableName, string $name, DataTypeField $dataTypeField, ?string $referencedColumnName = null)
	{
		$this->table                = $tableName;
		$this->name                 = $name;
		$this->dataTypeField        = $dataTypeField;
		$this->referencedColumnName = $referencedColumnName;
	}

	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->table;
	}

	/**
	 * @param string $table
	 */
	public function setTableName(string $table): void
	{
		$this->table = $table;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @return DataTypeField
	 */
	public function getDataTypeField(): DataTypeField
	{
		return $this->dataTypeField;
	}

	/**
	 * @param DataTypeField $dataTypeField
	 */
	public function setDataTypeField(DataTypeField $dataTypeField): void
	{
		$this->dataTypeField = $dataTypeField;
	}

	/**
	 * @return string|null
	 */
	public function getReferencedColumnName(): ?string
	{
		return $this->referencedColumnName;
	}

	/**
	 * @param string|null $referencedColumnName
	 */
	public function setReferencedColumnName(?string $referencedColumnName): void
	{
		$this->referencedColumnName = $referencedColumnName;
	}
}
