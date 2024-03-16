<?php

namespace WebImage\Models\Defs;

use WebImage\Models\Security\RoleAccessInterface;

class PropertyDefinition
{
	private string $name = '';
	/** @var string The name of the model to which this property belongs */
	private string $model = '';
	private string $dataType = '';
	private string $friendlyName = '';
	/** @var array */
	private $security = [];
	/** @var bool */
	private $isPrimaryKey = false;
	/** @var ?string */
	private ?string $generationStrategy = null;

	/** @var bool */
	private $isRequired = false;
	/** @var bool */
	private $isMultiValued = false;
	/** @var mixed */
	private $default;
	/** @var int */
	private $sortorder = 0;
	/** @var bool */
	private $readOnly = false;
	/** @var bool */
	private $searchable = false;
	/** @var string */
	private $reference;
	/** @var string */
	private string $comment = '';

	private int $size  = 0; // Used for "length" in VARCHAR or "precision" in DECIMAL
	private int $size2 = 0; // Used for "scale" in DECIMAL

	/**
	 * FieldDefinitions constructor.
	 * @param string $model
	 * @param string $name
	 * @param string $dataType
	 */
	public function __construct(string $model, string $name, string $dataType)
	{
		$this->model    = $model;
		$this->name     = $name;
		$this->dataType = $dataType;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
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

	public function getDataType(): string
	{
		return $this->dataType;
	}

	public function setDataType(string $dataType): void
	{
		$this->dataType = $dataType;
	}

	public function addSecurity(RoleAccessInterface $access)
	{
		$this->security[] = $access;
	}

	public function isVirtual(): bool
	{
		return $this->getDataType() == 'virtual';
	}

	/**
	 * @return string
	 */
	public function getFriendlyName(): string
	{
		return $this->friendlyName;
	}

	/**
	 * @param string $friendly
	 */
	public function setFriendlyName(string $friendly): void
	{
		$this->friendlyName = $friendly;
	}

	/**
	 * @return array
	 */
	public function getSecurity(): array
	{
		return $this->security;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->isRequired;
	}

	/**
	 * @param bool $isRequired
	 */
	public function setIsRequired(bool $isRequired): void
	{
		$this->isRequired = $isRequired;
	}

	/**
	 * @return bool
	 */
	public function isMultiValued(): bool
	{
		return $this->isMultiValued;
	}

	/**
	 * @param bool $isMultiValued
	 */
	public function setIsMultiValued(bool $isMultiValued): void
	{
		$this->isMultiValued = $isMultiValued;
	}

	/**
	 * @return mixed
	 */
	public function getDefault()/* PHP 8 : mixed*/
	{
		return $this->default;
	}

	/**
	 * @param mixed $default
	 */
	public function setDefault(/* @TODO PHP 8: mixed */ $default): void
	{
		$this->default = $default;
	}

	/**
	 * @return int
	 */
	public function getSortorder(): int
	{
		return $this->sortorder;
	}

	/**
	 * @param int $sortorder
	 */
	public function setSortorder(int $sortorder): void
	{
		$this->sortorder = $sortorder;
	}

	/**
	 * @return bool
	 */
	public function isReadOnly(): bool
	{
		return $this->readOnly;
	}

	/**
	 * @param bool $readOnly
	 */
	public function setReadOnly(bool $readOnly): void
	{
		$this->readOnly = $readOnly;
	}

	/**
	 * @return bool
	 */
	public function isSearchable(): bool
	{
		return $this->searchable;
	}

	/**
	 * @param bool $searchable
	 */
	public function setIsSearchable(bool $searchable): void
	{
		$this->searchable = $searchable;
	}

	/**
	 * @return PropertyReferenceDefinition
	 */
	public function getReference(): ?PropertyReferenceDefinition
	{
		return $this->reference;
	}

	/**
	 * @param PropertyReferenceDefinition $reference
	 */
	public function setReference(PropertyReferenceDefinition $reference): void
	{
		$this->reference = $reference;
	}

	/**
	 * Convenience method to check if this property references a type
	 * @return bool
	 */
	public function hasReference(): bool
	{
		return $this->getReference() !== null;
	}

	/**
	 * @return string
	 */
	public function getComment(): string
	{
		return $this->comment;
	}

	/**
	 * @param string $comment
	 */
	public function setComment(string $comment): void
	{
		$this->comment = $comment;
	}

	/**
	 * @return bool
	 */
	public function isPrimaryKey(): bool
	{
		return $this->isPrimaryKey;
	}

	/**
	 * @param bool $isPrimaryKey
	 */
	public function setIsPrimaryKey(bool $isPrimaryKey): void
	{
		$this->isPrimaryKey = $isPrimaryKey;
	}

	/**
	 * @return string|null
	 */
	public function getGenerationStrategy(): ?string
	{
		return $this->generationStrategy;
	}

	/**
	 * @param string|null $generationStrategy
	 */
	public function setGenerationStrategy(?string $generationStrategy): void
	{
		$this->generationStrategy = $generationStrategy;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function setSize(int $size): void
	{
		$this->size = $size;
	}

	public function getSize2(): int
	{
		return $this->size2;
	}

	public function setSize2(int $size2): void
	{
		$this->size2 = $size2;
	}
}
