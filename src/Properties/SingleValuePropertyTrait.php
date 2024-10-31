<?php

namespace WebImage\Models\Properties;

trait SingleValuePropertyTrait
{
	/**
	 * @property mixed
	 */
	private $_value;
	private $_originalValue;
	private $_hasChanged = false;

	public function reset()
	{
		$this->_value = null;
	}

	/**
	 * @inheritdoc
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @inheritdoc
	 */
	public function getOriginalValue()
	{
		return $this->_originalValue;
	}

	/**
	 * @inheritdoc
	 */
	public function setValue($_value)
	{
		$this->_value = $_value;

		// No value checking is done since $_value could be any type, including array, etc.
		if ($this->isValueLoaded()) $this->_hasChanged = true;
	}

	/**
	 * @inheritdoc
	 */
	public function hasChanged(): bool
	{
		return $this->_hasChanged;
	}
}
