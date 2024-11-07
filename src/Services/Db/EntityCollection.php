<?php

namespace WebImage\Models\Services\Db;

use WebImage\Core\Collection;
use WebImage\Core\Dictionary;
use WebImage\Core\VarDumper;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Helpers\EntityDebugger;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Query\FilterBuilder;
use WebImage\Models\Services\EntityServiceInterface;

/**
 * @property EntityStub[] $data
 */
class EntityCollection extends Collection implements PropertyLoaderInterface
{
	private PropertyLoaderInterface $propertyLoader;

	private EntityService $entityService;

	/**
	 * @param EntityService $entityService
	 * @param PropertyLoaderInterface $propertyLoader
	 */
	public function __construct(EntityService $entityService, PropertyLoaderInterface $propertyLoader)
	{
		parent::__construct();
		$this->entityService = $entityService;
		$this->propertyLoader = $propertyLoader;
	}

//	public function getPropertyLoader(): PropertyLoader
//	{
//		return $this->propertyLoader;
//	}
//
//	public function setPropertyLoader(PropertyLoader $multiValuePropertyLoader): void
//	{
//		$this->propertyLoader = $multiValuePropertyLoader;
//	}

	public function add($item): void
	{
		$this->addEntity($item);
	}

	private function addEntity(EntityStub $entity): void
	{
		parent::add($entity);
	}

	/**
	 * Loads property values for all collected Entities.
	 * @param string $propertyNameName
	 * @param array $entities
	 * @return void
	 */
	public function loadPropertyForEntities(string $propertyNameName, array $entities): void
	{
		$this->propertyLoader->loadPropertyForEntities($propertyNameName, $this->toArray());

//		if (count($this) == 0) return;
//
//		$property  = $entity->getProperty($propertyName);
//		$repo      = $this->entityService->getRepository();
//		$reference = $property->getDef()->getReference();
//
//		if ($property === null) return;
//		else if (!$property->getDef()->hasReference()) return;
//
//		echo VarDumper::toText($this->propertyLoader);
////		print_r($this->propertyLoader); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
//
//		if ($property->getDef()->isMultiValued() && $property->getDef()->getReference()->getReverseProperty() === null) throw new \RuntimeException($entity->getModel() . '.' . $propertyName . ' does not have a reverse property for ' . $property->getDef()->getReference()->getTargetModel() . ', which is required in order to populate values');
//
//		// Mark all entity's properties as loaded
//		$this->each(function(EntityStub $entity) use ($propertyName) {
//			$property = $entity->getProperty($propertyName);
//			if ($property === null) return;
//			$property->setIsValueLoaded(true);
//		});
//
//		if ($property->getDef()->isMultiValued()) {
//			// Create a lookup for the entity's primary key => $ix
//			$lookup = $this->createLookup(
//				function(EntityStub $entity) { // Generate key for lookup
//					return $this->getPrimaryKey($entity);
//				},
//				function(EntityStub $entity, $ix) { // Map key to original index
//					return $ix;
//				}
//			);
//			$referencedEntities = $repo->from($reference->getTargetModel())
//									   ->buildWhere(function (FilterBuilder $builder) use ($reference) {
//										   $builder->in($reference->getReverseProperty(), $this->toArray());
//									   })
//									   ->execute();
//		} else {
//			$references = $this->filter(function(EntityStub $entity) use ($propertyName) {
//				$property = $entity->getProperty($propertyName);
//				return $property !== null && $property->getValue() instanceof EntityStub;
//			})->map(function(EntityStub $entity) use ($propertyName) {
//				$property = $entity->getProperty($propertyName);
//				return $property->getValue();
//			});
//
//			$referencedEntities = $repo->from($reference->getTargetModel())->within($references->toArray())->execute();
//		}
//
//		echo 'Referenced Entities: ' . $propertyName . PHP_EOL;
//		foreach($referencedEntities as $referencedEntity) {
//			echo '- ' . $referencedEntity->getModel() . PHP_EOL;
//			$source = $referencedEntity[$reference->getReverseProperty()];
//			if ($source === null) continue;
//
//			$sourcePrimaryKey = $this->getPrimaryKey($source);
//			if (!$lookup->has($sourcePrimaryKey)) continue;
//			$ix = $lookup->get($sourcePrimaryKey);
//
//			$property = $this[$ix]->getProperty($propertyName);
//			$property->addValue($referencedEntity);
//		}
	}

	/**
	 * Create a primary key => index lookup
	 * @param EntityStub[] $entities
	 * @return Dictionary
	 */
//	private function createEntityLookup(array $entities): Dictionary
//	{
//		$lookup = new Dictionary();
//
//		foreach($entities as $ix => $entity) {
//			$lookup->set($this->getPrimaryKey($entity), $ix);
//		}
//
//		return $lookup;
//	}

}
