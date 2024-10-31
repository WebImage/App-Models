<?php

/**
 * Keep track of changes to the collection so that they can be efficiently deleted in the repository storage
 */
namespace WebImage\Models\Properties;

use WebImage\Core\Collection;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityStub;

class MultiValueCollection extends Collection
{
	private bool $hasChanged = false;

	public function add($item): void
	{
		parent::add($item);
		$this->hasChanged = true;
	}

	public function insert($index, $item): void
	{
		parent::insert($index, $item);
		$this->hasChanged = true;
	}

	public function filter(callable $filterCallback): Collection
	{
		$newCollection = parent::filter($filterCallback);

		// Only mark new collection as changed if the number of items has changed
		if (count($this) != count($newCollection))
		{
			$newCollection->hasChanged = true;
		}

		return $newCollection;
	}

	public function __unset($name)
	{
		parent::__unset($name);
		$this->hasChanged = true;
	}

	public function __set($index, $value)
	{
		parent::__set($index, $value);
		$this->hasChanged = true;
	}

	/**
	 * Indicates whether the collection has changed.
	 * NOTE: This only checks whether the underlying list has changed, not whether any individual items have changed.
	 * @return bool
	 */
	public function hasChanged(): bool
	{
		return $this->hasChanged;
	}
}