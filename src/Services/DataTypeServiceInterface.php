<?php

namespace WebImage\Models\Services;

use WebImage\Core\Dictionary;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Entities\Entity;

/**
 * An interface for managing DataTypes and InputElementDefs (and probably display elements)
 */
interface DataTypeServiceInterface extends RepositoryAwareInterface
{
	/**
	 * @param string $propertyType
	 *
	 * @return DataTypeDefinition
	 */
	public function getDefinition(string $propertyType): ?DataTypeDefinition;

	/**
	 * Get all datatypes
	 * @return DataTypeDefinition[]
	 */
	public function getDefinitions(): array;

	/**
	 * Converts a value to a dictionary for use in storage
	 * @param string $propertyTypeName
	 * @param $value
	 * @return mixed
	 */
	public function valueForStorage(string $propertyTypeName, $value); /* PHP 8 : mixed*/

	/**
	 * Converts a dictionary to an value to be added to a Entity
	 * @param DataType $dataTypeName
	 * @param Dictionary $dictionary
	 * @return mixed
	 */
	public function valueForProperty(string $dataTypeName, $value);/* PHP 8 : mixed*/
}
