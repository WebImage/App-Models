<?php

namespace WebImage\Models\Services\Db;

use Exception;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Entities\Model;
use WebImage\Models\Services\AbstractModelService;
use WebImage\Models\Services\ModelServiceInterface;

class ModelService extends AbstractModelService implements ModelServiceInterface
{
	/** @var ConnectionManager */
	private ConnectionManager     $connectionManager;
	private ?DoctrineTableCreator $tableCreator;
	private TableNameHelper       $tableNameHelper;

	/**
	 * ModelService constructor.
	 */
	public function __construct(ConnectionManager $connectionManager, TableNameHelper $tableNameHelper)
	{
		$this->connectionManager = $connectionManager;
		$this->tableNameHelper   = $tableNameHelper;
		$this->tableCreator      = null;
	}

	public function save(Model $model): ?Model
	{
		$this->getTableCreator()->importModels([$model->getDef()]);

		return $model;
	}

	/**
	 * @throws Exception
	 */
	public function delete(Model $model): bool
	{
		throw new Exception(__METHOD__ . ' is not supported by this ModelService');
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
