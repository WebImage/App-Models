<?php

namespace WebImage\Models\Properties;

class MultiValueProperty extends AbstractProperty implements MultiValuePropertyInterface
{
	/**
	 * @var MultiValueCollection Captures the original values once setIsValueLoaded(...) has been called.
	 */
	private MultiValueCollection $originalValues;
	/**
	 * @property MultiValueCollection $values An array of values
	 **/
	private MultiValueCollection $values;

	public function __construct()
	{
		parent::__construct();
		$this->originalValues = new MultiValueCollection();
	}

	/**
	 * @inheritdoc
	 */
	public function getValues(): MultiValueCollection
	{
		return $this->values;
	}

	/**
	 * @inheritdoc
	 */
	public function setValues(array $values)
	{
		$this->values = new MultiValueCollection();

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
		$this->values = new MultiValueCollection();
	}

	public function hasChanged(): bool
	{
		return $this->values->hasChanged();
	}

	public function getOriginalValues(): MultiValueCollection
	{
		return $this->originalValues;
	}

	public function setIsValueLoaded(bool $loaded): void
	{
		parent::setIsValueLoaded($loaded);
		$this->captureOriginalValues();
	}

	/**
	 * Captures all current values into the originalValues collection
	 */
	private function captureOriginalValues()
	{
		$this->originalValues = new MultiValueCollection();
		foreach($this->values as $value) {
			$this->originalValues[] = $value;
		}
	}
}
