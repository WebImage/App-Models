<?php

namespace WebImage\Models\Entities;


use Exception;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Services\RepositoryAwareInterface;
use WebImage\Models\Services\RepositoryAwareTrait;

class Model implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;

	/** @var ModelDefinitionInterface */
	private $def;

	/**
	 * Save the node type to the service
	 */
	public function save()
	{
		$this->getRepository()->getModelService()->save($this);
	}

	/**
	 * Delete the node type from the service
	 */
	public function delete()
	{
		$this->getRepository()->getModelService()->delete($this);
	}

	/**
	 * Creates a property definition and adds it to the list of properties
	 * @param string $key
	 * @param string $type
	 * @param bool $required
	 * @param $default
	 * @param bool $isMultiValued
	 * @param int|null $sortorder
	 * @return PropertyDefinition
	 * @throws Exception
	 */
	public function createProperty(string $key, string $type, bool $required = false, $default = null, bool $isMultiValued = false, int $sortorder = null): PropertyDefinition
	{
		// Check if property already exists with this key
		if ($this->getDef()->getProperty($key)) {
			throw new Exception('Property already exists');
		}

		$def = new PropertyDefinition($this->getDef()->getName(), $key, $type);
		$def->setIsRequired($required);
		$def->setDefault($default);
		$def->setIsMultiValued($isMultiValued);
		if ($sortorder !== null) $def->setSortorder($sortorder);

		$this->getDef()->addProperty($def);

		return $def;
	}

	public function getModelStack()
	{
		return [$this];
	}
//	/**
//	 * Retrieves a fresh copy of the underlying Entity.  Should not cache, as the underlying Node may have changed and needs to be refreshed.
//	 * @return null|Node
//	 */
//	public function getEntity()
//	{
//		return $this->getRepository()->getNodeService()->getNodeByUuid($this->getDef()->getUuid());
//	}
//
//	public function createAssociation($friendlyName, $associatedTypeQName, $assocTypeQName)
//	{
//		$associatedType = $associated_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($associatedTypeQName);
//
//		return $this->getRepository()->getNodeTypeService()->createAssociation($friendlyName, $this, $associatedType, $assocTypeQName);
//	}

//	/**
//	 * Retrieves all properties from this type's definition, all parent type definitions, and all extension definitions (as opposed to $this->getDef()->getProperties() which only returns properties for this properties definition
//	 *
//	 * @return PropertyDefinition[]|Dictionary of property definitions
//	 */
//	public function getProperties()
//	{
//		// Get all associated types for this type
//		$typeStack = $this->getTypeStack();
//		// Instantiate return object
//		$properties = new Dictionary();
//
//		// Iterate through types and add properties to Dictionary
//		foreach ($typeStack as $type) {
//
//			foreach ($type->getDef()->getProperties() as $key => $property) {
//				$properties->set($key, $property);
//			}
//		}
//
//		return $properties;
//	}

	/**
	 * @return ModelDefinition
	 */
	public function getDef()
	{
		return $this->def;
	}

	/**
	 * @param ModelDefinitionInterface $def
	 */
	public function setDef(ModelDefinitionInterface $def)
	{
		$this->def = $def;
	}
}
