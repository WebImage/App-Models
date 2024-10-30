<?php

namespace WebImage\Models\Properties;

use WebImage\Core\Collection;

class MultiValueProperty extends AbstractProperty implements MultiValuePropertyInterface
{
	/**
	 * @property Collection $values An array of values
	 **/
	private Collection $values;

	/**
	 * @inheritdoc
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @inheritdoc
	 */
	public function setValues(array $values)
	{
		$this->values = new Collection();

		foreach ($values as $value) {
			$this->addValue($value);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function addValue($value)
	{
		$this->values[] = $value;
	}

	public function reset()
	{
		$this->values = new Collection();
	}
}
