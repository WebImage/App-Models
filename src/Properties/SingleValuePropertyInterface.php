<?php

namespace WebImage\Models\Properties;

use WebImage\Core\Dictionary;

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
	 * @param string|int|string[]|mixed|Dictionary $value
	 */
	public function setValue($value);

	public function getOriginalValue();
}
