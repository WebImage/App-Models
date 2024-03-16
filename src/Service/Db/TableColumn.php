<?php

namespace WebImage\Models\Service\Db;

use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\DataTypeField;

class TableColumn
{
	/** @var string */
	private $table;
	/** @var string */
	private $name;
	/** @var ?string */
	private $referencedColumnName;
	/** @var DataTypeField */
	private $dataTypeField;

	/**
	 * PropertyTableColumn constructor.
	 * @param string $name
	 * @param DataTypeDefinition $dataTypeField
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
