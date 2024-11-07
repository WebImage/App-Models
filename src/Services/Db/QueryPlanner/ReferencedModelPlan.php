<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Core\ArrayHelper;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\MultiValueProperty;
use WebImage\Models\Properties\Property;
use WebImage\Models\Services\Db\DbEntityReference;
use WebImage\Models\Services\Db\LazyEntity;
use WebImage\Models\Services\Db\PropertyLoaderInterface;
use WebImage\Models\Services\EntityDebugger;
use WebImage\Models\Services\RepositoryInterface;

/**
 * @method ReferencePropertyPlan[] getColumns()
 */
class ReferencedModelPlan extends PropertyPlan
{
	private string $referencedModel;

	public function __construct(string $model, string $property, string $referencedModel, array $columns)
	{
		parent::__construct($model, $property, $columns);
		$this->referencedModel = $referencedModel;
	}

	public function getReferencedModel(): string
	{
		return $this->referencedModel;
	}

	protected function assertValidColumns(array $columns)
	{
		ArrayHelper::assertItemTypes($columns, ReferencePropertyPlan::class);
	}

	public function buildEntity(RepositoryInterface $repo, ConnectionManager $connectionManager/*, ModelDefinitionInterface $modelDef*/, EntityStub $entity, array $result, PropertyLoaderInterface $propertyLoader)
	{
		$property = $entity->getProperty($this->getProperty());
		if ($property === null) throw new \Exception('Property not found: ' . $this->getProperty());

		$refEntity = $repo->createEntityReference($this->getReferencedModel());

		if ($refEntity instanceof DbEntityReference) {
			$refEntity->setPropertyLoader($propertyLoader);
		}

		foreach ($this->getColumns() as $column) {
			$column->buildEntity($repo, $connectionManager, $refEntity, $result, $propertyLoader);
		}

		$property->setValue($refEntity);
	}
}