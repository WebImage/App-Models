<?php

namespace WebImage\Models\Properties;

use WebImage\Models\Defs\PropertyDefinition;

abstract class AbstractProperty implements PropertyInterface
{
	private bool $isValueLoaded = false;
	/**
	 * @var PropertyDefinition
	 */
	private PropertyDefinition $def;

	public function __construct()
	{
		$this->reset();
	}

	/**
	 * Get the entity's def
	 *
	 * @return PropertyDefinition
	 */
	public function getDef(): PropertyDefinition
	{
		return $this->def;
	}

	/**
	 * Set the entity's definition
	 *
	 * @param PropertyDefinition $def
	 */
	public function setDef(PropertyDefinition $def)
	{
		$this->def = $def;
	}

	public function isValueLoaded(): bool
	{
		return $this->isValueLoaded;
	}

	public function setIsValueLoaded(bool $loaded): void
	{
		$this->isValueLoaded = $loaded;
	}

	/**
	 * @inheritdoc
	 */
	abstract public function reset();
}
