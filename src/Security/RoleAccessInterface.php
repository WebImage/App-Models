<?php

/**
 * Represents a role's access level
 */
namespace WebImage\Models\Security;

class RoleAccessInterface implements ContextAccessInterface
{
	private string $role = '';
	private bool $create;
	private bool $read = false;
	private bool $update = false;
	private bool $delete;

	/**
	 * RoleAccess constructor.
	 * @param string $role
	 * @param bool $create
	 * @param bool $read
	 * @param bool $update
	 * @param bool $delete
	 */
	public function __construct(string $role, bool $create, bool $read, bool $update, bool $delete)
	{
		$this->role = $role;
		$this->create = $create;
		$this->read = $read;
		$this->update = $update;
		$this->delete = $delete;
	}

	/**
	 * @return string
	 */
	public function getRole(): string
	{
		return $this->role;
	}

	/**
	 * @return bool
	 */
	public function canCreate(): bool
	{
		return $this->create;
	}

	/**
	 * @return bool
	 */
	public function canRead(): bool
	{
		return $this->read;
	}

	/**
	 * @return bool
	 */
	public function canUpdate(): bool
	{
		return $this->update;
	}

	/**
	 * @return bool
	 */
	public function canDelete(): bool
	{
		return $this->delete;
	}
}
