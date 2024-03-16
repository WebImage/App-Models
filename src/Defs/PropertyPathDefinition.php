<?php

namespace WebImage\Models\Defs;

class PropertyPathDefinition
{
	/** @var string */
	private $targetType;
	/** @var ?string */
	private $property;
	/** @var ?string */
	private $forwardProperty;

	/**
	 * PropertyPathDefinition constructor.
	 * @param string $targetType
	 * @param string $property
	 */
	public function __construct(string $targetType, ?string $property, ?string $forwardProperty)
	{
		$this->targetType      = $targetType;
		$this->property        = $property;
		$this->forwardProperty = $forwardProperty;
	}

	/**
	 * @return string
	 */
	public function getTargetModel(): string
	{
		return $this->targetType;
	}

	/**
	 * @param string $targetType
	 */
	public function setTargetType(?string $targetType): void
	{
		$this->targetType = $targetType;
	}

	/**
	 * @return string
	 */
	public function getProperty(): ?string
	{
		return $this->property;
	}

	/**
	 * @param string $property
	 */
	public function setProperty(string $property): void
	{
		$this->property = $property;
	}

	/**
	 * @return string|null
	 */
	public function getForwardProperty(): ?string
	{
		return $this->forwardProperty;
	}

	/**
	 * @param string|null $forwardProperty
	 */
	public function setForwardProperty(?string $forwardProperty): void
	{
		$this->forwardProperty = $forwardProperty;
	}
}
