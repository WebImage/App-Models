<?php

namespace WebImage\Models\Query;

class Property
{
	/**
	 * @var string|null
	 */
	private ?string $modelName;
	/**
	 * @var string
	 */
	private string $property;

	/**
	 * Property constructor.
	 *
	 * @param string $sProperty
	 */
	public function __construct(string $sProperty)
	{
		list($typeName, $property) = $this->parseProperty($sProperty);
		$this->modelName = $typeName;
		$this->property  = $property;
	}

	private function parseProperty($str): array
	{
		$parts    = explode('.', $str);
		$property = array_pop($parts);
		$modelName = count($parts) > 0 ? implode('.', $parts) : null;

		return [$modelName, $property];
	}

	/**
	 * Get the type name
	 *
	 * @return string
	 */
	public function getModelName(): ?string
	{
		return $this->modelName;
	}

	/**
	 * Get the property
	 *
	 * @return string
	 */
	public function getProperty(): string
	{
		return $this->property;
	}
}
