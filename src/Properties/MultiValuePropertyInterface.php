<?php

namespace WebImage\Models\Properties;

interface MultiValuePropertyInterface extends PropertyInterface
{
	/**
	 * Return values
	 *
	 * @TODO Not sure SingleValuePropertyInterface is valid???
	 * @return SingleValuePropertyInterface[]
	 */
	public function getValues();

	/**
	 * Sets the root values
	 *
	 * @param SingleValuePropertyInterface[] $values
	 */
	public function setValues(array $values);

	/**
	 * Get the original values
	 * @return mixed
	 */
	public function getOriginalValues();

	/**
	 * Add a value to the internal collection
	 *
	 * @param string|SingleValuePropertyInterface $value
	 */
	public function addValue($value);

	/**
	 * Reset everything to a blank slate
	 */
	public function reset();
}
