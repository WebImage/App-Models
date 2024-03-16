<?php

namespace WebImage\Models\Query;

class Property
{
	/**
	 * @var string
	 */
	private $modelName;
	/**
	 * @var string
	 */
	private $property;

	/**
	 * Property constructor.
	 *
	 * @param string $sProperty
	 */
	public function __construct($sProperty)
	{
		list($typeName, $property) = $this->parseProperty($sProperty);
		$this->modelName = $typeName;
		$this->property  = $property;
	}

	private function parseProperty($str)
	{
		$parts    = explode('.', $str);
		$property = array_pop($parts);
		$typeName = count($parts) > 0 ? implode('.', $parts) : null;

		return [$typeName, $property];
	}

	/**
	 * Get the type name
	 *
	 * @return string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}

	/**
	 * Get the property
	 *
	 * @return string
	 */
	public function getProperty()
	{
		return $this->property;
	}
}
