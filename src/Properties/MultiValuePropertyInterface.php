<?php

namespace WebImage\Models\Properties;

interface MultiValuePropertyInterface extends PropertyInterface
{
	/**
	 * Return values
	 *
	 * @return MultiValueCollection
	 */
	public function getValues(): MultiValueCollection;

	/**
	 * Sets the root values
	 *
	 * @param iterable $values
	 */
	public function setValues(iterable $values);

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
