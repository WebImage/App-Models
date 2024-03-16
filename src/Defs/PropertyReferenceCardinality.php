<?php

namespace WebImage\Models\Defs;

class PropertyReferenceCardinality
{
	const CARDINALITY_ONE      = '1';
	const CARDINALITY_MULTIPLE = 'n';
	const DIRECTION_UNI        = 'uni';
	const DIRECTION_BI         = 'bi';
	const DIRECTION_SELF       = 'self';

	/** @var ?string */
	private $sourceCardinality;
	/** @var ?string */
	private $targetCardinality;
	/** @var ?string */
	private $direction;

	/**
	 * PropertyReferenceCardinality constructor.
	 * @param string $sourceCardinality
	 * @param string $targetCardinality
	 * @param string $direction
	 */
	public function __construct(string $sourceCardinality=null, string $targetCardinality=null, ?string $direction=null)
	{
		$this->sourceCardinality = $sourceCardinality;
		$this->targetCardinality = $targetCardinality;
		$this->direction         = $direction;
	}

	/**
	 * @return string|null
	 */
	public function getSourceCardinality(): ?string
	{
		return $this->sourceCardinality;
	}

	/**
	 * @param string|null $cardinality
	 */
	public function setSourceCardinality(?string $cardinality): void
	{
		$this->validateCardinality($cardinality);
		$this->sourceCardinality = $cardinality;
	}

	/**
	 * @return string|null
	 */
	public function getTargetCardinality(): ?string
	{
		return $this->targetCardinality;
	}

	/**
	 * @param string|null $cardinality
	 */
	public function setTargetCardinality(?string $cardinality): void
	{
		$this->validateCardinality($cardinality);
		$this->targetCardinality = $cardinality;
	}

	/**
	 * @return bool
	 */
	public function isSourceMultiple()
	{
		return $this->getSourceCardinality() == self::CARDINALITY_MULTIPLE;
	}

	/**
	 * @return bool
	 */
	public function isTargetMultiple()
	{
		return $this->getTargetCardinality() == self::CARDINALITY_MULTIPLE;
	}

	/**
	 * @return string|null
	 */
	public function getDirection(): ?string
	{
		return $this->direction;
	}

	/**
	 * @param string|null $direction
	 */
	public function setDirection(?string $direction): void
	{
		$this->validateDirection($direction);
		$this->direction = $direction;
	}

	/**
	 * Convenience method to check if this is a one-to-one relationship
	 *
	 * @return bool
	 */
	public function isOneToOne(): bool
	{
		return !$this->isSourceMultiple() && !$this->isTargetMultiple();
	}

	/**
	 * Convenience method to check if this is a one-to-many relationship
	 *
	 * @return bool
	 */
	public function isOneToMany(): bool
	{
		return !$this->isSourceMultiple() && $this->isTargetMultiple();
	}

	/**
	 * Convenience method to check if this is a many-to-one relationship
	 *
	 * @return bool
	 */
	public function isManyToOne(): bool
	{
		return $this->isSourceMultiple() && !$this->isTargetMultiple();
	}

	/**
	 * Convenience method to check if this is a many-to-many relationship
	 *
	 * @return bool
	 */
	public function isManyToMany(): bool
	{
		return $this->isSourceMultiple() && $this->isTargetMultiple();
	}

	public function __toString(): string
	{
		$sourceCardinality = $this->getSourceCardinality() === null ? '?' : $this->getSourceCardinality();
		$targetCardinality = $this->getTargetCardinality() === null ? '?' : $this->getTargetCardinality();
		$direction         = $this->getDirection() === null ? '?' : $this->getDirection();

		return sprintf('%s:%s (%s)', $sourceCardinality, $targetCardinality, $direction);
	}

	private function validateCardinality(?string $cardinality): void
	{
		$allowed = [null, self::CARDINALITY_ONE, self::CARDINALITY_MULTIPLE];

		if (!in_array($cardinality, $allowed)) {
			$displayAllowed = array_map(function(?string $cardinality) {
				return $cardinality === null ? 'NULL' : $cardinality;
			}, $allowed);
			throw new \InvalidArgumentException('cardinality must be one of ' . implode(', ', $displayAllowed));
		}
	}

	private function validateDirection(?string $direction): void
	{
		$allowed = [null, self::DIRECTION_UNI, self::DIRECTION_BI, self::DIRECTION_SELF];

		if (!in_array($direction, $allowed)) {
			$displayAllowed = array_map(function(?string $direction) {
				return $direction === null ? 'NULL' : $direction;
			}, $allowed);
			throw new \InvalidArgumentException('direction must be one of ' . implode(', ', $displayAllowed));
		}
	}
}
