<?php

namespace WebImage\Models\Defs;

class PropertyReferenceDefinition
{
	/** @var string The name of the type being referenced*/
	private $targetModel;
	/** @var string */
	private $reverseProperty;
	/** @var PropertyPathDefinition[] */
	private $path = [];
	/** @var string The property to be selected from the final result in an association/join */
	private $selectProperty;

	/**
	 * PropertyEntityReferenceDefinition constructor.
	 * @param string $type
	 */
	public function __construct(string $type)
	{
		$this->targetModel = $type;
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
	public function setTargetModel(string $targetModel): void
	{
		$this->targetModel = $targetModel;
	}

	/**
	 * @return bool
	 */
	public function hasReverseProperty(): bool
	{
		return $this->reverseProperty !== null;
	}

	/**
	 * @return string
	 */
	public function getReverseProperty(): ?string
	{
		return $this->reverseProperty;
	}

	/**
	 * @param string|null $reverseProperty
	 */
	public function setReverseProperty(?string $reverseProperty): void
	{
		if (is_string($reverseProperty) && strlen($reverseProperty) == 0) {
			throw new \InvalidArgumentException('Cannot set ' . $this->getTargetModel() . ' reverse type to empty string');
		}

		$this->reverseProperty = $reverseProperty;
	}

	/**
	 * @return PropertyPathDefinition[]
	 */
	public function getPath(): array
	{
		return $this->path;
	}

	/**
	 * @param PropertyPathDefinition[] $path
	 */
	public function setPath(array $path): void
	{
		$this->path = [];

		foreach($path as $tPath) {
			$this->addPath($tPath);
		}
	}

	public function addPath(PropertyPathDefinition $path)
	{
		$this->path[] = $path;
	}

	/**
	 * @return string
	 */
	public function getSelectProperty(): ?string
	{
		return $this->selectProperty;
	}

	/**
	 * @param string $selectProperty
	 */
	public function setSelectProperty(?string $selectProperty): void
	{
		$this->selectProperty = $selectProperty;
	}
}
