<?php

namespace WebImage\Models\Services;

use WebImage\Models\Entities\Entity;
use WebImage\Models\Query\QueryBuilder;
use WebImage\Models\Services\RepositoryInterface;

abstract class ModelRepository
{
	private RepositoryInterface $repo;
	private string $model;

	public function __construct(RepositoryInterface $repo, string $model)
	{
		$this->setRepo($repo);
		$this->setModel($model);
	}

	public function getRepo(): RepositoryInterface
	{
		return $this->repo;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	private function setRepo(RepositoryInterface $repo): void
	{
		$this->repo = $repo;
	}

	private function setModel(string $model): void
	{
		$this->model = $model;
	}

	public function createEntity(): Entity
	{
		return $this->repo->getEntityService()->create($this->model);
	}

	public function query(): QueryBuilder
	{
		return $this->repo->from($this->model);
	}
}
