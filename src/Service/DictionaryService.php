<?php

namespace WebImage\Models\Service;

use WebImage\Core\Dictionary;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;

class DictionaryService implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;

	/**
	 * @property Dictionary of ModelDef
	 */
	private $models;
	/**
	 * @property Dictionary<string $alias, string $modelName>
	 */
	private $modelAliases;
	/**
	 * @property array[string]DataType Dictionary of DataType
	 */
	private $propertyTypes;
	/**
	 * @var Dictionary
	 */
	private $propertyTypesAliases;
//	/**
//	 * @property Dictionary of NodeAssociationDef
//	 */
//	private $associations;

	public function __construct()
	{
		/**
		 * Instantiate type object
		 */
		$this->models = new Dictionary();
		$this->modelAliases = new Dictionary();
		/**
		 * Instantiate property types object
		 */
		$this->propertyTypes = new Dictionary();

		$this->propertyTypeAliases = new Dictionary();
	}

	/**
	 * Get a model by name
	 *
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function getModel(string $name): ?ModelDefinitionInterface
	{
		/**
		 * If no model is found under $name, then check if there is an alias to the correct name
		 */
		if (!$this->models->has($name) && $this->modelAliases->has($name)) {
			$name = $this->modelAliases->get($name);
		}

		return $this->models->get($name);
	}

	/**
	 * @return Dictionary|ModelDefinitionInterface[] A dictionary of defined models
	 */
	public function getModels()
	{
		return $this->models;
	}

	/**
	 * @param ModelDefinitionInterface $model
	 */
	public function addModel(ModelDefinitionInterface $model)
	{
		$this->models->set($model->getName(), $model);
		$this->modelAliases->set($model->getPluralName(), $model->getName());
	}

//	/**
//	 * @param ModelDefinitionInterface $model
//	 */
//	public function setModel(ModelDefinitionInterface $model)
//	{
//		$this->models->set($model->getName(), $model);
//	}

	/**
	 * @param $type
	 *
	 * @return DataType[string]|null
	 */
	public function getPropertyType($type): ?DataTypeDefinition
	{
		if ($this->propertyTypeAliases->has($type)) $type = $this->propertyTypeAliases->get($type);

		return $this->propertyTypes->get($type);
	}

	/**
	 * @return Dictionary<string, DataType> A dictionary of defined data types
	 */
	public function getPropertyTypes(): array
	{
		return array_values($this->propertyTypes->toArray());
	}

	/**
	 * @param DataTypeDefinition $propTypeDef
	 */
	public function addPropertyType(DataTypeDefinition $propTypeDef)
	{
		$this->propertyTypes->set($propTypeDef->getName(), $propTypeDef);
	}

	/**
	 * @param string $alias
	 * @param string $propertyType
	 */
	public function setPropertyTypeAlias(string $alias, string $propertyType)
	{
		$this->propertyTypeAliases->set($alias, $propertyType);
	}

//	public function getAssociation($assocTypeQName)
//	{
//		if ($association = $this->associations->get($assocTypeQName)) {
//			return $association;
//		} else {
//			return null;
//		}
//	}



//	public function addAssociation($associationDef)
//	{
//		$this->associations->set($associationDef->getQName(), $associationDef);
//	}



//	public function setAssociation(NodeAssociationDef $def)
//	{
//		$this->associations->set($def->getQName(), $def);
//	}
}
