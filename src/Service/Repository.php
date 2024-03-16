<?php

namespace WebImage\Models\Service;

use Psr\Log\LoggerInterface;
use Psr\Logger;
use WebImage\Models\Query\QueryBuilder;

class Repository implements RepositoryInterface
{
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
	 * @param EntityServiceInterface $entityService
	 * @param ModelServiceInterface $modelService
	 * @param DictionaryService $dictionaryService
	 * @param DataTypeServiceInterface $dataTypesService
	 */
	public function __construct(EntityServiceInterface $entityService, ModelServiceInterface $modelService, DictionaryService $dictionaryService, DataTypeServiceInterface $dataTypesService)
	{
		$this->setEntityService($entityService);
		$this->setTypeService($modelService);
		$this->setDictionaryService($dictionaryService);
		$this->setDataTypeService($dataTypesService);
	}

	public function from(string $model): QueryBuilder
	{
		$queryBuilder = $this->getEntityService()->createQueryBuilder();
		$queryBuilder->from($model);

		return $queryBuilder;
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
