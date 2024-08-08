<?php

namespace WebImage\Models\Services\Db;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;

class PropertiesColumns
{
	/** @var Dictionary|ModelPropertyTableColumns[] */
	private $properties;

	/**
	 * PropertiesColumns constructor.
	 */
	public function __construct()
	{
		$this->properties = new Dictionary();
	}

	/**
	 * @param string $property
	 * @param ModelPropertyTableColumns $tableColumns
	 */
	public function setPropertyColumns(string $property, ModelPropertyTableColumns $tableColumns): void
	{
		$this->properties->set($property, $tableColumns);
	}

	/**
	 * @param string $property
	 * @return ModelPropertyTableColumns
	 */
	public function getPropertyColumns(string $property): ModelPropertyTableColumns
	{
		if (!$this->properties->has($property)) die('Missing Property: ' . $property . PHP_EOL);
		return $this->properties->get($property);
	}

	/**
	 * @return ImmutableDictionary|ModelPropertyTableColumns[]
	 */
	public function getProperties(): ImmutableDictionary
	{
		return new ImmutableDictionary($this->properties->toArray());
	}

	/**
	 * Returns ALL columns for all properties
	 *
	 * @return TableColumn[]
	 */
	public function getColumns(): array
	{
		$columns = [];

		foreach($this->properties as $tableColumns) {
			foreach($tableColumns->getColumns() as $column) {
				$columns[] = $column;
			}
		}

		return $columns;
	}
}
