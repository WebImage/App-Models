<?php

namespace WebImage\Models\Services;

use Psr\Log\LoggerInterface;
use WebImage\Event\EventManager;
use WebImage\Event\EventManagerInterface;
use WebImage\Event\Manager;
use WebImage\Models\Query\QueryBuilder;

class Repository implements RepositoryInterface
{
	/** @var EventManagerInterface */
	private $events;
	/** @var EntityServiceInterface */
	private $entityService;
	/** @var ModelServiceInterface */
	private $modelService;
	/** @var DictionaryService */
	private $dictionaryService;
	/** @var DataTypeServiceInterface */
	private $dataTypesService;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * Repository constructor.
	 * @param EventManagerInterface $entityService
	 * @param EntityServiceInterface $entityService
	 * @param ModelServiceInterface $modelService
	 * @param DictionaryService $dictionaryService
	 * @param DataTypeServiceInterface $dataTypesService
	 */
	public function __construct(EventManagerInterface $eventManager,
								EntityServiceInterface $entityService,
								ModelServiceInterface $modelService,
								DictionaryService $dictionaryService,
								DataTypeServiceInterface $dataTypesService)
	{
		$this->events = $eventManager;
		$this->setEntityService($entityService);
		$this->setTypeService($modelService);
		$this->setDictionaryService($dictionaryService);
		$this->setDataTypeService($dataTypesService);
		$this->events->trigger('repository.models.load', null, $this);
	}

	public function from(string $model): QueryBuilder
	{
		$queryBuilder = $this->getEntityService()->createQueryBuilder();
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
