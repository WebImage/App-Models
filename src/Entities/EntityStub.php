<?php

namespace WebImage\Models\Entities;

use WebImage\Core\Collection;
use WebImage\Core\Dictionary;
use WebImage\Models\Exceptions\InvalidPropertyException;
use WebImage\Models\Properties\MultiValuePropertyInterface;
use WebImage\Models\Properties\PropertyInterface;
use WebImage\Models\Properties\SingleValuePropertyInterface;

class EntityStub implements \ArrayAccess
{
	/**
	 * @var string
	 */
	private string $model;
	/**
	 * @var Dictionary
	 */
	private $properties = [];
	/**
	 * @var bool
	 */
	private bool $isValid = true; // Assume the best

	/**
	 * Node constructor.
	 * @param string $model
	 */
	public function __construct(string $model)
	{
		$this->model      = $model;
		$this->properties = new Dictionary();
	}

	/**
	 * Get the properties associated with the node
	 *
	 * @return Dictionary|PropertyInterface[]|SingleValuePropertyInterface[]|MultiValuePropertyInterface[]
	 */
	public function getProperties(): Dictionary
	{
		return $this->properties;
	}

	/**
	 * Get a specific property
	 *
	 * @param $name
	 * @return PropertyInterface|SingleValuePropertyInterface|MultiValuePropertyInterface|null
	 */
	public function getProperty($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * Get the value for a given property
	 *
	 * @param string $name
	 * @return mixed
	 */
	private function rawPropertyValue(string $name)
	{
		$property = $this->getProperty($name);
		if ($property === null) return null;

		if ($property instanceof MultiValuePropertyInterface) {
			throw new InvalidPropertyException('Requesting single value on a multi-valued property: ' . $name);
		}

		return $property->getValue();
	}

	private function rawMultiPropertyValues(string $name)
	{
		$property = $this->getProperty($name);
		if ($property === null) throw new InvalidPropertyException('Unknown property ' . $name . ' on ' . $this->getModel());

		if ($property instanceof SingleValuePropertyInterface) {
			throw new InvalidPropertyException('Requesting multi-value on single-value property');
		}

		return $property->getValues();
	}

	/**
	 * Get the value for a given property
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue(string $name)
	{
		$property = $this->getProperty($name);
		if ($property === null) return null;

		if ($property->getDef()->isMultiValued()) {
			return $this->rawMultiPropertyValues($name);
		}

		return $this->rawPropertyValue($name);
	}

	/**
	 * @return string
	 */
	public function getModel(): string
	{
		return $this->model;
	}

	/**
	 * Add a property to the node. Used to add properties initially without triggering an object changed
	 *
	 * @param string $name
	 * @param PropertyInterface $property
	 */
	public function addProperty(string $name, PropertyInterface $property): void
	{
		$this->properties[$name] = $property;
	}

	/**
	 * Set a node for a property
	 *
	 * @param $name
	 * @param PropertyInterface $property
	 */
	public function setProperty(string $name, PropertyInterface $property): void
	{
		$this->properties[$name] = $property;
	}

	/**
	 * Set the property value
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setPropertyValue(string $name, $value): void
	{
		$property = $this->getProperty($name);
		if ($property === null) throw new \RuntimeException('Cannot set non-existent property: ' . $this->getModel() . '.' . $name);

		if ($property instanceof SingleValuePropertyInterface) {
			$property->setValue($value);
		} else if ($property instanceof MultiValuePropertyInterface) {
			// @TODO May need to check if $value is actually an array first
//			$property->setValues([$value]);
			$property->setValues($value);
		} else {
			throw new \RuntimeException('Unknown property type: ' . $this->getModel() . '.' . $name);
		}
	}

	/**
	 * Set's the node's type
	 *
	 * @param string $model
	 */
	public function setModel(string $model): void
	{
		$this->model = $model;
	}

	/**
	 * Set multiple properties
	 * @param array $properties
	 */
	private function setProperties(array $properties): void
	{
		foreach($properties as $name => $property) {
			$this->setProperty($name, $property);
		}
	}

	public function hasChanged(): bool
	{
		foreach($this->getProperties() as $property) {
			if ($property->hasChanged()) return true;
		}

		return false;
	}

	public function isValid(): bool
	{
		return $this->isValid;
	}

	protected function setIsValid(bool $valid): void
	{
		$this->isValid = $valid;
	}

	public function offsetExists($offset): bool
	{
		return $this->getProperty($offset) !== null;
	}

	public function offsetGet($offset)
	{
		return $this->getPropertyValue($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->setPropertyValue($offset, $value);
	}

	public function offsetUnset($offset)
	{
		throw new \RuntimeException('Not implemented');
	}

	public function __toString()
	{
		return $this->getModel() . '[' . implode(',', $this->getProperties()->keys()) . ']';
	}
}
