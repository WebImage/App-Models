<?php

namespace WebImage\Models\Services;

use WebImage\Core\Collection;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Compiler\InvalidModelException;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Query\Query;
use WebImage\Models\Query\QueryBuilder;

interface EntityServiceInterface extends RepositoryAwareInterface
{
	const EVENT_SAVING = 'repository.entity.saving';
	const EVENT_SAVED = 'repository.entity.saved';

	public function get(): Entity;

	/**
	 * @param string $modelName
	 * @return Entity
	 * @throws InvalidModelException
	 */
	public function create(string $modelName): Entity;
	public function createReference(string $modelName): EntityReference;
	public function save(Entity $entity): Entity;
	public function delete(Entity $entity): bool;

	/**
	 * @param Query $query
	 * @return Collection
	 */
	public function query(Query $query): Collection; //Entity[]
	public function createQueryBuilder(): QueryBuilder;
	#public function createAssociation($);
}
