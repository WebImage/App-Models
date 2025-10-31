<?php

namespace WebImage\Models\Defs;

class PropertyPathDefinition
{
	/** @var string */
	private $targetModel;
	/** @var ?string */
	private $property;
	/** @var ?string */
	private $forwardProperty;

	/**
	 * PropertyPathDefinition constructor.
	 * @param string $targetModel
	 * @param string $property
	 */
	public function __construct(string $targetModel, ?string $property, ?string $forwardProperty)
	{
		$this->targetModel      = $targetModel;
		$this->property        = $property;
		$this->forwardProperty = $forwardProperty;
	}

	/**
	 * @return string
	 */
	public function getTargetModel(): string
	{
		return $this->targetModel;
	}

	/**
	 * @param string $targetModel
	 */
	public function setTargetModel(?string $targetModel): void
	{
		$this->targetModel = $targetModel;
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
