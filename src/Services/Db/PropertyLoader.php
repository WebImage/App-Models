<?php

namespace WebImage\Models\Services\Db;

use WebImage\Core\ArrayHelper;
use WebImage\Core\Collection;
use WebImage\Core\Dictionary;
use WebImage\Core\VarDumper;
use WebImage\Models\Defs\PropertyReferenceCardinality;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Query\FilterBuilder;
use WebImage\Models\Services\RepositoryInterface;

class PropertyLoader implements PropertyLoaderInterface
{
	private RepositoryInterface $repository;

	/**
	 * @param EntityQueryService $entityQueryService
	 */
	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function loadPropertyForEntities(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;
		ArrayHelper::assertItemTypes($entities, EntityStub::class);

		foreach($entities as $entity) {
			$property = $entity->getProperty($propertyName);
			if ($property === null) throw new \RuntimeException('Expecting to find property ' . $propertyName . ' on entity ' . $entity->getModel());
			else if (!$property->getDef()->hasReference()) throw new \RuntimeException('Property ' . $propertyName . ' on ' . $entity->getModel() . ' does not have a reference.');
			$property->setIsValueLoaded(true);
		}

		if ($property->getDef()->isMultiValued()) $this->loadMultiValueProperty($propertyName, $entities);
		else $this->loadSingleValueProperty($propertyName, $entities);
	}

	/**
	 * @param string $propertyName
	 * @param EntityStub[] $entities
	 * @return void
	 */
	private function loadSingleValueProperty(string $propertyName, array $entities): void
	{
		if (count($entities) == 0) return;

		$cardinality = PropertyReferenceHelper::getAssociationCardinality($this->repository->getModelService(), $entities[0]->getProperty($propertyName)->getDef());

		if ($cardinality->isTargetMultiple()) throw new \RuntimeException('loadingSingleValueProperties is not yet supported when target cardinality is multiple');

		/** @var Dictionary $lookup */
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
				$entities[$ix]->setPropertyValue($propertyName, $propertyEntity);
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
		$lookup = new Dictionary();
		foreach($entities as $ix => $entity) {
			$lookup->set($this->getPrimaryKey($entity), $ix);
		}

		$reference = $entity->getProperty($propertyName)->getDef()->getReference();

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
		}
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
}
