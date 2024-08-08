<?php

namespace WebImage\Models\Services;

use Psr\Log\LoggerInterface;
use WebImage\Event\EventManager;
use WebImage\Event\EventManagerInterface;
use WebImage\Event\Manager;
use WebImage\Models\Compiler\InvalidModelException;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Query\QueryBuilder;

class Repository implements RepositoryInterface
{
	/** @var EventManagerInterface */
	private EventManagerInterface $events;
	/** @var EntityServiceInterface */
	private EntityServiceInterface $entityService;
	/** @var ModelServiceInterface */
	private ModelServiceInterface $modelService;
	/** @var DictionaryService */
	private DictionaryService $dictionaryService;
	/** @var DataTypeServiceInterface */
	private DataTypeServiceInterface $dataTypesService;
	private ?LoggerInterface $logger;

	/**
	 * Repository constructor.
	 * @param EventManagerInterface $eventManager
	 * @param EntityServiceInterface $entityService
	 * @param ModelServiceInterface $modelService
	 * @param DictionaryService $dictionaryService
	 * @param DataTypeServiceInterface $dataTypesService
	 */
	public function __construct(EventManagerInterface    $eventManager,
								EntityServiceInterface   $entityService,
								ModelServiceInterface    $modelService,
								DictionaryService        $dictionaryService,
								DataTypeServiceInterface $dataTypesService)
	{
		$this->events = $eventManager;
		$this->setEntityService($entityService);
		$this->setTypeService($modelService);
		$this->setDictionaryService($dictionaryService);
		$this->setDataTypeService($dataTypesService);
		$this->events->trigger('repository.models.load', null, $this);
		$this->logger = null;
	}

	public function from(string $model): QueryBuilder
	{
		$queryBuilder = $this->getEntityServiceForModel($model)->createQueryBuilder();
		$queryBuilder->from($model);

		return $queryBuilder;
	}

	public function getEventManager(): EventManagerInterface
	{
		return $this->events;
	}

	public function getEntityService(): EntityServiceInterface
	{
		return $this->entityService;
	}

	/**
	 * @throws InvalidModelException
	 */
	public function createEntity(string $model): Entity
	{
		return $this->getEntityServiceForModel($model)->create($model);
	}

	public function createEntityReference(string $model): EntityReference
	{
		return $this->getEntityServiceForModel($model)->createReference($model);
	}

	public function saveEntity(Entity $entity): Entity
	{
		return $this->getEntityServiceForModel($entity->getModel())->save($entity);
	}

	public function deleteEntity(Entity $entity): bool
	{
		return $this->getEntityServiceForModel($entity->getModel())->delete($entity);
	}

	private function getEntityServiceForModel(string $model): EntityServiceInterface
	{
		return $this->getEntityService();
	}


	public function getModelService(): ModelServiceInterface
	{
		return $this->modelService;
	}

	public function getDictionaryService(): DictionaryService
	{
		return $this->dictionaryService;
	}

	public function getDataTypeService(): DataTypeServiceInterface
	{
		return $this->dataTypesService;
	}

	private function setEntityService(EntityServiceInterface $entityService)
	{
		$this->entityService = $entityService;
		$entityService->setRepository($this);
	}

	private function setTypeService(ModelServiceInterface $typeService)
	{
		$this->modelService = $typeService;
		$typeService->setRepository($this);
	}

	private function setDictionaryService(DictionaryService $dictionaryService)
	{
		$this->dictionaryService = $dictionaryService;
		$dictionaryService->setRepository($this);
	}

	private function setDataTypeService(DataTypeServiceInterface $dataTypeService)
	{
		$this->dataTypesService = $dataTypeService;
		$dataTypeService->setRepository($this);
	}

	public function getLogger(): ?LoggerInterface
	{
		return $this->logger;
	}

	public function setLogger(?LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
