<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Core\ArrayHelper;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\Property;
use WebImage\Models\Services\Db\PropertyLoaderInterface;
use WebImage\Models\Services\EntityDebugger;
use WebImage\Models\Services\RepositoryInterface;

class ReferencePropertyPlan extends PropertyPlan
{
	private string $referencedProperty;
	public function __construct(string $model, string $property, string $refProperty, array $columns)
	{
		parent::__construct($model, $property, $columns);
		$this->referencedProperty = $refProperty;
	}

	public function getReferencedProperty(): string
	{
		return $this->referencedProperty;
	}

//	public function buildEntity(RepositoryInterface $repo, ConnectionManager $connectionManager, EntityStub $entity, array $result, PropertyLoaderInterface $propertyLoader)
//	{
//
//		$property = $entity->getProperty($this->getReferencedProperty());
////		$propDef = $modelDef->getProperty($this->getReferencedProperty());
////		$property = new Property();
////		$property->setDef($propDef);
////		$this->buildValue($repo, $connectionManager, $modelDef, $property, $result, $propertyLoader);
//		$this->buildValue($repo, $connectionManager, $entity, $result, $propertyLoader);
////		echo '<pre>';print_r($entity); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
////		$entity->addProperty($propDef->getName(), $property);
//	}

	public function getBuildValueProperty(): string
	{
		return $this->getReferencedProperty();
	}
}