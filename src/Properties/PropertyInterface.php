<?php

namespace WebImage\Models\Properties;

use WebImage\Models\Defs\PropertyDefinition;

interface PropertyInterface {
	/**
	 * Get the definition for an entity
	 *
	 * @return PropertyDefinition
	 */
	public function getDef(): PropertyDefinition;

	/**
	 * Set the definition for an entity
	 *
	 * @param PropertyDefinition $def
	 */
	public function setDef(PropertyDefinition $def);

	/**
	 * Reset the property value to its original state
	 */
	public function reset();

	/**
	 *
	 * @return bool
	 */
	public function isValueLoaded(): bool;
	public function setIsValueLoaded(bool $loaded): void;
	public function hasChanged(): bool;
}
