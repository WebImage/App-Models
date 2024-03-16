<?php

namespace WebImage\Models\Service\Db;

class AssociationTable
{
	/** @var string */
	private $tableName;

	/** @var array */
	private $targetPropertyColumns = [];

	/**
	 * AssociationTable constructor.
	 * @param string $tableName
	 * @param AssociationTableTarget $source
	 * @param AssociationTableTarget $target
	 */
	public function __construct(string $tableName, AssociationTableTarget $source, AssociationTableTarget $target)
	{
		$this->tableName = $tableName;
		$this->source    = $source;
		$this->target    = $target;
	}
	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->tableName;
	}
	/**
	 * @return string
	 */
	public function getSource(): AssociationTableTarget
	{
		return $this->source;
	}

	public function getTarget(): AssociationTableTarget
	{
		return $this->target;
	}
}
