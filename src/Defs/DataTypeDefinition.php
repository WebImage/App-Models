<?php

namespace WebImage\Models\Defs;

use WebImage\Models\DataTypes\ValueMapper;

class DataTypeDefinition {
	private $name;
	private $friendlyName; /* Friendly Name */
	/** @var string $valueMapper A mappers that converts dictionary values to a class */
	private $valueMapper;
	/** @var DataTypeField[] */
	private $modelFields = [];
//	/** @var string A name resolvable to an input element **/
//	private $defaultFormElement;
	/** @var string $view */
	private $view;

	function __construct(string $name, string $friendlyName, string $valueMapper=null, string $view=null)
	{
		$this->setName($name);
		$this->setFriendlyName($friendlyName);
		if (null !== $valueMapper) $this->setValueMapper($valueMapper);
		if (null !== $view) $this->setView($view);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getFriendlyName(): string
	{
		return $this->friendlyName;
	}

	/**
	 * @return string
	 */
	public function getValueMapper(): ?string
	{
		return $this->valueMapper;
	}

	/**
	 * @return string
	 */
	public function getView(): string
	{
		return $this->view;
	}

	/**
	 * @return DataTypeField[]
	 */
	public function getModelFields()
	{
		return $this->modelFields;
	}

//	/**
//	 * @return mixed
//	 */
//	public function getDefaultFormElement()
//	{
//		return $this->defaultFormElement;
//	}

	/**
	 * @param string $name
	 */
	private function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @param string $name
	 */
	public function setFriendlyName(string $name)
	{
		$this->friendlyName = $name;
	}

//	/**
//	 * Sets a reference to a resolvable input element
//	 * @param string $inputElement
//	 * @return string
//	 */
//	public function setDefaultFormElement(string $inputElement)
//	{
//		return $this->defaultFormElement;
//	}

	/**
	 * A valuable resolvable to a ValueMapper
	 * @param $valueMapper
	 */
	public function setValueMapper(string $valueMapper)
	{
		$this->valueMapper = $valueMapper;
	}

	/**
	 * @param DataTypeField $field
	 */
	public function addTypeField(DataTypeField $field)
	{
		$this->modelFields[] = $field;
	}

	/**
	 * Whether or not this data type contains a simple single-column storage field (which must not have a name)
	 *
	 * @return bool
	 */
	public function isSimpleStorage()
	{
		return (count($this->modelFields) == 1 && strlen($this->modelFields[0]->getKey()) == 0);
	}

	/**
	 * @param string $view
	 */
	public function setView(string $view)
	{
		$this->view = $view;
	}
}
