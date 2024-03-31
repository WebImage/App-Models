<?php

namespace WebImage\Models\Services;

use WebImage\Event\EventManagerInterface;
use WebImage\Models\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

interface RepositoryInterface
{
	public function from(string $model): QueryBuilder;
	public function getEventManager(): EventManagerInterface;
	/**
	 * Get the node service
	 *
	 * @return EntityServiceInterface
	 */
	public function getEntityService(): EntityServiceInterface;

	/**
	 * Get the node type service
	 * @return ModelServiceInterface
	 */
	public function getModelService(): ModelServiceInterface;

	/**
	 * Get the dictionary service
	 *
	 * @return DictionaryService
	 */
	public function getDictionaryService(): DictionaryService;

	/**
	 * Get the data type service
	 *
	 * @return DataTypeServiceInterface
	 */
	public function getDataTypeService(): DataTypeServiceInterface;

	/**
	 * Get a logger that can be used to log various details
	 *
	 * @return LoggerInterface|null
	 */
	public function getLogger(): ?LoggerInterface;

	/**
	 * Set a logger to use for logging operations
	 * @param LoggerInterface|null $logger
	 * @return mixed
	 */
	public function setLogger(?LoggerInterface $logger);
}
