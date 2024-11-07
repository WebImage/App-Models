<?php

namespace WebImage\Models\Services\Db;

use Exception;
use WebImage\Core\ArrayHelper;
use WebImage\Core\Collection;
use WebImage\Core\Dictionary;
use WebImage\Core\VarDumper;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\PropertyReferenceCardinality;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Helpers\EntityDebugger;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Query\FilterBuilder;
use WebImage\Models\Services\Db\QueryPlanner\ModelQueryPlanner;
use WebImage\Models\Services\RepositoryInterface;

class PropertyLoader implements PropertyLoaderInterface
{
	private RepositoryInterface $repository;
	private ConnectionManager $connectionManager;

	/**
	 * @param RepositoryInterface $repository
	 * @param ConnectionManager $connectionManager
	 */
	public function __construct(RepositoryInterface $repository, ConnectionManager $connectionManager)
	{
		$this->repository = $repository;
		$this->connectionManager = $connectionManager;
	}

	/**
	 * @throws Exception
	 */
	public function loadPropertyForEntities(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;
		ArrayHelper::assertItemTypes($entities, EntityStub::class);

		$singleValueProperties = [];
		$multiValueProperties = [];

		// Separate entities into multi- and single-value properties
		foreach($entities as $entity) {
			$property = $entity->getProperty($propertyName);
			if ($property === null) throw new \RuntimeException('Expecting to find property ' . $propertyName . ' on entity ' . $entity->getModel());
			else if (!$property->getDef()->hasReference()) throw new \RuntimeException('Property ' . $propertyName . ' on ' . $entity->getModel() . ' does not have a reference.');
			if ($property->getDef()->isMultiValued()) $multiValueProperties[] = $entity;
			else $singleValueProperties[] = $entity;
		}

		$this->loadMultiValueProperty($propertyName, $multiValueProperties);
		$this->loadSingleValueProperty($propertyName, $singleValueProperties);;
	}

	/**
	 * @param string $propertyName
	 * @param EntityStub[] $entities
	 * @return void
	 * @throws Exception
	 */
	private function test(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;

		echo 'PropertyLoader: ' .  $propertyName . '<br/>' . PHP_EOL;
		$lookup = new Dictionary();
		$propDef = $entities[0]->getProperty($propertyName)->getDef();
		$dataTypeService = $this->repository->getDataTypeService();
		$modelService = $this->repository->getModelService();

		$dataType = $dataTypeService->getDefinition($propDef->getDataType());
		$modelDef = $modelService->getModel($propDef->getModel())->getDef();
		$propertyColumns = TableHelper::getPropertyColumns($modelService, $modelDef, $propertyName);

		if ($propDef->isMultiValued()) {
			echo 'Use table: ' . $propDef->getName() . '<br/>' . PHP_EOL;
			if ($propDef->isVirtual()) {
				// SELECT *
				// FROM listings_p_categories
				// LEFT JOIN categories ON categories.id = listings_p_categories.categories_id
				// WHERE listing_p_customers.listings_id = 1;
				$associationTable = TableHelper::getAssociationTable($modelService, $propDef->getModel(), $propDef->getReference()->getTargetModel(), $propertyName, $propDef->getReference()->getReverseProperty());

//				$qb = $this->repository
				echo 'SELECT *' . '<br/>';
				echo 'FROM ' . $associationTable->getTableName() . '<br>';
				echo 'INNER JOIN ' . $associationTable->getTarget()->getTableName() . ' ON ';

				foreach($associationTable->getTarget()->getPropertiesColumns()->getProperties() as $key => $property) {
					foreach($property->getColumns() as $propertyColumn) {
						echo TableNameHelper::getTableColumnName($associationTable->getTarget()->getTableName(), $propertyColumn->getName()) . ' = ';
						echo TableNameHelper::getTableColumnName($associationTable->getTableName(), $associationTable->getTarget()->getTableName(), $propertyColumn->getName()) . '<br/>' . PHP_EOL;
					}
				}

//				echo '<pre>';print_r($associationTable->getTarget()->getPropertiesColumns()->getProperties()); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
				echo '<pre>';print_r($associationTable); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
			} else {
				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			}
			die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
		} else {
			foreach($entities as $entity) {
				$property = $entity->getProperty($propertyName);
				if ($propDef->isVirtual() && $property->getValue() === null) continue;
//				echo '<pre>';
//				echo $property->getValue() === null ? 'NULL' : 'NOT NULL';
//				print_r($property);
//				print_r(EntityDebugger::summarize($entity));
//				echo get_class($entity) . '<br/>' . PHP_EOL;
//				$value = $entity[$propertyName];
				die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			}
		}
		return;
		$from = null;

		foreach($entities as $ix => $entity) {
			$primaryKey = $this->getPrimaryKey($entity);
			$targetModel = $entity->getProperty($propertyName)->getDef()->getReference()->getTargetModel();
			$lookup->set($primaryKey, $ix);
			if ($from === null) {
				$from = $targetModel;
			} else if ($targetModel != $from) {
				throw new \RuntimeException('All target models must be the same');
			}
		}

		echo $propertyName . ' FROM: ' . $from . '<br/>' . PHP_EOL;
		return;
		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
		$this->repository->from($from)->buildWhere(function(FilterBuilder $builder) {});
		echo '<pre>';print_r($lookup); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
	}

	/**
	 * @param Dictionary $primaryKeys
	 * @param string $propertyName
	 * @param EntityStub[] $entities
	 * @return void
	 * @throws Exception
	 */
	private function loadSingleValueProperty(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;
		$cardinality = PropertyReferenceHelper::getAssociationCardinality($this->repository->getModelService(), $entities[0]->getProperty($propertyName)->getDef());

		if ($cardinality->isTargetMultiple()) throw new \RuntimeException('loadingSingleValueProperties is not yet supported when target cardinality is multiple');

		$lookup = new Dictionary();
		$propertyEntityRefs = [];

		/**
		 * Create target entity look
		 */
		foreach($entities as $ix => $entity) {
			$property = $entity->getProperty($propertyName);
			if ($property ===  null) continue;
			else if (!($property->getValue() instanceof EntityStub)) continue;
			$key = $this->getPrimaryKey($property->getValue());
			if (!$lookup->has($key)) {
				$lookup->set($key, new Collection());
				$propertyEntityRefs[] = $property->getValue();
			}
			$lookup->get($key)->add($ix);
		}

		$property = $entities[0]->getProperty($propertyName);
		$propertyReference = $property->getDef()->getReference();

		$propertyEntities = $this->repository->from($propertyReference->getTargetModel())->within($propertyEntityRefs)->execute();

		foreach($propertyEntities as $propertyEntity) {
			$key = $this->getPrimaryKey($propertyEntity);
			if (!$lookup->has($key)) continue; // This would be WEIRD

			foreach($lookup->get($key) as $ix) {
				$property = $entities[$ix]->getProperty($propertyName);
				$property->setValue($propertyEntity);
				$property->setIsValueLoaded(true);
			}
		}
	}

	/**
	 * @param string $propertyName
	 * @param EntityStub[] $entities
	 * @return void
	 */
	private function loadMultiValueProperty(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;

		$reverseReferenceEntities = [];
		$directReferenceEntities = [];

		foreach($entities as $ix => $entity) {
			if ($entity->getProperty($propertyName)->getDef()->getReference()->hasReverseProperty()) $reverseReferenceEntities[] = $entity;
			else $directReferenceEntities[] = $entity;
		}

		$this->loadReverseReferenceMultiValueProperty($propertyName, $reverseReferenceEntities);
		$this->loadMultiValueVirtualProperty($propertyName, $directReferenceEntities);
	}

	private function loadReverseReferenceMultiValueProperty(string $propertyName, array $reverseReferenceEntities): void
	{
		if (count($reverseReferenceEntities) == 0) return;

		$lookup = new Dictionary();

		foreach($reverseReferenceEntities as $ix => $entity) {
			$lookup->set($this->getPrimaryKey($entity), $ix);
		}

		$reference = $reverseReferenceEntities[0]->getProperty($propertyName)->getDef()->getReference();

		$referencedEntities = $this->repository->from($reference->getTargetModel())
											   ->buildWhere(function (FilterBuilder $builder) use ($reference, $entities) {
												   $builder->in($reference->getReverseProperty(), $entities);
											   })
											   ->execute();

		// Assign referenced entities back to
		foreach($referencedEntities as $referencedEntity) {
			$source = $referencedEntity[$reference->getReverseProperty()];
			if ($source === null) continue;

			$sourcePrimaryKey = $this->getPrimaryKey($source);
			if (!$lookup->has($sourcePrimaryKey)) continue;
			$ix = $lookup->get($sourcePrimaryKey);

			$property = $entities[$ix]->getProperty($propertyName);
			$property->addValue($referencedEntity);
			$property->setIsValueLoaded(true);
		}
	}
	private function loadMultiValueVirtualProperty(string $propertyName, array $directReferenceEntities): void
	{
		if (count($directReferenceEntities) == 0) return;

		$reference = $directReferenceEntities[0]->getProperty($propertyName)->getDef()->getReference();
//
		echo 'Property: ' . $propertyName . '<br/>' . PHP_EOL;
//
		/*
		 * SELECT *
		 *  FROM listings_p_categories
		 * INNER JOIN categories ON categories.id = listings_p_categories.categories_id
		 * WHERE listing_p_customers.listings_id IN (1)
		 */

//		print_r(EntityDebugger::summarize($directReferenceEntities[0]));
//		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
//		print_r($reference);
//		echo '<hr>';
//		print_r($entities);
//		echo '<pre>';print_r($lookup); die(__FILE__ . ':' . __LINE__ . PHP_EOL);
//		$referencedEntities = $this->repository->from($reference->getTargetModel())
//											   ->buildWhere(function (FilterBuilder $builder) use ($reference, $entities) {
//												   $builder->in($reference->getReverseProperty(), $entities);
//											   })
//											   ->execute();
//
//		// Assign referenced entities back to
//		foreach($referencedEntities as $referencedEntity) {
//			$source = $referencedEntity[$reference->getReverseProperty()];
//			if ($source === null) continue;
//
//			$sourcePrimaryKey = $this->getPrimaryKey($source);
//			if (!$lookup->has($sourcePrimaryKey)) continue;
//			$ix = $lookup->get($sourcePrimaryKey);
//
//			$property = $entities[$ix]->getProperty($propertyName);
//			$property->addValue($referencedEntity);
//			$property->setIsValueLoaded(true);
//		}
	}

	private function getPrimaryKey(EntityStub $entity): string
	{
		$modelService = $this->repository->getModelService();
		$primaryKeys = $modelService->getModel($entity->getModel())->getDef()->getPrimaryKeys();
		$values = [];

		foreach($primaryKeys as $primaryKey) {
			$values[] = $entity[$primaryKey->getName()];
		}

		return implode('--', $values);
	}

	/**
	 * @param EntityStub[] $entities
	 * @return Dictionary
	 */
	private function createPrimaryKeyLookupForEntities(iterable $entities): Dictionary
	{
		$modelService = $this->repository->getModelService();

		$lookup = new Dictionary();
		$modelKeys = new Dictionary();
		foreach($entities as $entity) {
			if (!$modelKeys->has($entity->getModel())) $modelKeys->set($entity->getModel(), $modelService->getModel($entity->getModel())->getDef()->getPrimaryKeys()->keys());
			$primaryKeys = $modelKeys->get($entity->getModel());
			$valueMap = [];
			foreach($primaryKeys as $primaryKey) {
				$valueMap[$primaryKey] = $entity[$primaryKey];
			}
			$lookupKey = implode('--', array_values($valueMap));
			$lookup->set($lookupKey, $valueMap);
		}

		return $lookup;
	}
}
