<?php

namespace WebImage\Models\Services\Db;

class AssociationTableTarget
{
	/** @var string */
	private $tableName;
	/** @var string */
	private $type;
	/** @var ?string */
	private $property;
	/** @var PropertiesColumns */
	private $propertyColumns;

	/**
	 * AssociationTableTarget constructor.
	 * @param string $type
	 * @param string|null $property
	 * @param PropertiesColumns $propertyColumns
	 */
	public function __construct(string $tableName, string $type, ?string $property, PropertiesColumns $propertyColumns)
	{
		$this->tableName       = $tableName;
		$this->type            = $type;
		$this->property        = $property;
		$this->propertyColumns = $propertyColumns;
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
	public function getPropertyColumns(): PropertiesColumns
	{
		return $this->propertyColumns;
	}
}
