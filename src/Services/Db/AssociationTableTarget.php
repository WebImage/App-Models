<?php

namespace WebImage\Models\Services\Db;

class AssociationTableTarget
{
	/** @var string */
	private $tableName;
	/** @var string */
	private $type;
	/** @var ?string */
	private ?string $property;
	/** @var PropertiesColumns */
	private PropertiesColumns $propertiesColumns;

	/**
	 * AssociationTableTarget constructor.
	 * @param string $type
	 * @param string|null $property
	 * @param PropertiesColumns $propertiesColumns
	 */
	public function __construct(string $tableName, string $type, ?string $property, PropertiesColumns $propertiesColumns)
	{
		$this->tableName       = $tableName;
		$this->type            = $type;
		$this->property        = $property;
		$this->propertiesColumns = $propertiesColumns;
	}

	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->tableName;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return string|null
	 */
	public function getProperty(): ?string
	{
		return $this->property;
	}

	/**
	 * @return PropertiesColumns
	 */
	public function getPropertiesColumns(): PropertiesColumns
	{
		return $this->propertiesColumns;
	}
}
