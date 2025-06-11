<?php

namespace WebImage\Models\Services;

use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\Model;

abstract class AbstractModelService implements ModelServiceInterface
{
	use RepositoryAwareTrait;

	/**
	 * @return Model[]
	 */
	public function all(): array
	{
		return array_map(function(ModelDefinitionInterface $modelDef) {
			return $this->getModel($modelDef->getName());
		}, $this->getRepository()->getDictionaryService()->getModelDefinitions());
	}

	public function getModel(string $name): ?Model
	{
		$def = $this->getRepository()->getDictionaryService()->getModelDefinition($name);

		$model = null;

		if ($def !== null) {
			$model = new Model();
			$model->setRepository($this->getRepository());
			$model->setDef($def);
		}

		return $model;
	}

	public function create(string $name, string $pluralName, string $friendlyName, string $pluralFriendlyName): ?Model
	{
		$modelDef = new ModelDefinition($name, $pluralName, $friendlyName, $pluralFriendlyName);

		$model = new Model();
		$model->setRepository($this->getRepository());
		$model->setDef($modelDef);

		// Add type definition to dictionary
		$this->getRepository()->getDictionaryService()->addModelDefinition($modelDef);

		return $model;
	}
}
