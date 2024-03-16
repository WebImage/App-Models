<?php

namespace WebImage\Models\Properties;

interface SingleValuePropertyInterface extends PropertyInterface
{
	/**
	 * Returns the value for a given property...
	 *
	 * @return mixed Could be anything
	 */
	public function getValue();


	/**
	 * Set the property value
	 *
	 * @param string|int|array[string]mixed|Dictionary $value
	 */
	public function setValue($value);
}
