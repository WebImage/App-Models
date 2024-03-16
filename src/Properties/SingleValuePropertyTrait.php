<?php

namespace WebImage\Models\Properties;

trait SingleValuePropertyTrait
{
	/**
	 * @property mixed
	 */
	private $_value;

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
	public function setValue($_value)
	{
		$this->_value = $_value;
	}
}
