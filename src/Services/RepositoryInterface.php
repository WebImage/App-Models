<?php

namespace WebImage\Models\Services;

use WebImage\Event\EventManagerInterface;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

interface RepositoryInterface
{
	/**
	 * Create a QueryBuilder for the requested model
	 * @param string $model
	 * @return QueryBuilder
	 */
	public function from(string $model): QueryBuilder;

	/**
	 * Create an entity for the requested model
	 * @param string $model
	 * @return Entity
	 */
	public function createEntity(string $model): Entity;
	/**
	 * Create an entity reference for the requested model
	 * @param string $model
	 * @return EntityReference
	 */
	public function createEntityReference(string $model): EntityReference;
	/**
	 * Save an entity
	 * @param Entity $entity
	 * @return Entity
	 */
	public function saveEntity(Entity $entity): Entity;
	/**
	 * Delete an entity
	 * @param Entity $entity
	 * @return bool
	 */
	public function deleteEntity(Entity $entity): bool;

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
