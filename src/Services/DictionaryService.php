<?php

namespace WebImage\Models\Services;

use WebImage\Core\Dictionary;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;

class DictionaryService implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;

	/**
	 * @property Dictionary of ModelDef
	 */
	private Dictionary $modelDefs;
	/**
	 * @property Dictionary<string $alias, string $modelName>
	 */
	private Dictionary $modelAliases;
	/**
	 * @property array[string]DataType Dictionary of DataType
	 */
	private Dictionary $propertyTypes;
	/**
	 * @var Dictionary
	 */
	private Dictionary $propertyTypesAliases;
//	/**
//	 * @property Dictionary of NodeAssociationDef
//	 */
//	private $associations;
	private Dictionary $propertyTypeAliases;

	public function __construct()
	{
		$this->resetModelDefinitions();
		/**
		 * Instantiate property types object
		 */
		$this->propertyTypes       = new Dictionary();
		$this->propertyTypeAliases = new Dictionary();
	}

    /**
     * Reset model definitions back to their original state
     * @return void
     */
    public function resetModelDefinitions(): void
    {
        /**
         * Instantiate model object
         */
        $this->modelDefs    = new Dictionary();
        $this->modelAliases = new Dictionary();
    }

	/**
	 * Get a model by name
	 *
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function getModelDefinition(string $name): ?ModelDefinitionInterface
	{
		/**
		 * If no model is found under $name, then check if there is an alias to the correct name
		 */
		if (!$this->modelDefs->has($name) && $this->modelAliases->has($name)) {
			$name = $this->modelAliases->get($name);
		}

		return $this->modelDefs->get($name);
	}

	/**
	 * @return ModelDefinitionInterface[] A dictionary of defined models
	 */
	public function getModelDefinitions(): array
	{
		return array_values($this->modelDefs->toArray());
	}

	/**
	 * @param ModelDefinitionInterface $model
	 */
	public function addModelDefinition(ModelDefinitionInterface $model): void
	{
        $this->removeModelDefinition($model->getName());
		$this->modelDefs->set($model->getName(), $model);
		$this->modelAliases->set($model->getPluralName(), $model->getName());
	}

    public function removeModelDefinition(string $modelName): void
    {
        /** @var ModelDefinitionInterface|null $model */
        $model = $this->modelDefs->get($modelName);
        if ($model === null) return;

        $this->modelDefs->del($model->getName());
        $this->modelAliases->del($model->getPluralName());
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
	 * @return DataTypeDefinition[string]|null
	 */
	public function getPropertyType($type): ?DataTypeDefinition
	{
		if ($this->propertyTypeAliases->has($type)) $type = $this->propertyTypeAliases->get($type);

		return $this->propertyTypes->get($type);
	}

	/**
	 * @return array<string, DataTypeDefinition> A dictionary of defined data types
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
