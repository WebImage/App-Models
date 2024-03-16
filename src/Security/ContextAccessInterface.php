<?php

namespace WebImage\Models\Security;

interface ContextAccessInterface
{
	/**
	 * @return bool
	 */
	public function canCreate(): bool;

	/**
	 * @return bool
	 */
	public function canRead(): bool;

	/**
	 * @return bool
	 */
	public function canUpdate(): bool;

	/**
	 * @return bool
	 */
	public function canDelete(): bool;
}
