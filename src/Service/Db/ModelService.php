<?php

namespace WebImage\Models\Service\Db;

use WebImage\Db\ConnectionManager;
use WebImage\Models\Entities\Model;
use WebImage\Models\Service\AbstractModelService;
use WebImage\Models\Service\Exception;
use WebImage\Models\Service\ModelServiceInterface;

class ModelService extends AbstractModelService implements ModelServiceInterface
{
	/** @var ConnectionManager */
	private $connectionManager;
	/** @var TableNameHelper */
	private $tableNameHelper;
	/** @var DoctrineTableCreator */
	private $tableCreator;
	/**
	 * ModelService constructor.
	 */
	public function __construct(ConnectionManager $connectionManager, TableNameHelper $tableNameHelper)
	{
		$this->connectionManager = $connectionManager;
		$this->tableNameHelper   = $tableNameHelper;
	}

	public function save(Model $model): ?Model
	{
		$this->getTableCreator()->importModels([$model->getDef()]);

		return $model;
	}

	public function delete(Model $model): bool
	{
		throw new \Exception(__METHOD__  . ' is not supported by this ModelService');
	}

	/**
	 * @return ConnectionManager
	 */
	public function getConnectionManager(): ConnectionManager
	{
		return $this->connectionManager;
	}

	private function getTableCreator(): DoctrineTableCreator
	{
		if ($this->tableCreator === null) {
			$this->tableCreator = new DoctrineTableCreator($this);
		}

		return $this->tableCreator;
	}
}
