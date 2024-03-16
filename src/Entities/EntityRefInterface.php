<?php

namespace WebImage\Models\Entities;

interface EntityRefInterface
{
	/**
	 * Get the unique identifier for the node
	 *
	 * @return string
	 */
	public function uuid();

	/**
	 * Get the current version of the node reference
	 *
	 * @return long
	 */
	public function version();
}
