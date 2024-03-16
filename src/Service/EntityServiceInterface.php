<?php

namespace WebImage\Models\Service;

use WebImage\Core\Collection;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Compiler\InvalidModelException;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Query\Query;
use WebImage\Models\Query\QueryBuilder;

interface EntityServiceInterface extends RepositoryAwareInterface
{
	public function get(): Entity;

	/**
	 * @param string $model
	 * @return Entity
	 * @throws InvalidModelException
	 */
	public function create(string $model): Entity;
	public function createReference(string $type): EntityReference;
	public function save(Entity $entity): Entity;
	public function delete(Entity $entity): bool;
	/** @return Entity[] */
	public function query(Query $query): Collection; //Entity[]
	public function createQueryBuilder(): QueryBuilder;
	#public function createAssociation($);
}
