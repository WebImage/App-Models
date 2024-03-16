<?php

namespace WebImage\Models\Query;

class Sort extends Property
{
	/**
	 * @var string
	 */
	private $direction; // ASC or DESC

	/**
	 * Sort constructor.
	 *
	 * @param string $field (preferably fully-qualified field name with type qname, e.g. App.System.Types.SomeType.propertyKey
	 * @param null $sortDirection
	 */
	public function __construct(string $field, $sortDirection = null)
	{
		parent::__construct($field);
		$this->direction = $sortDirection;
	}

	public function getDirection()
	{
		return $this->direction;
	}
}
