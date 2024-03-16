<?php

namespace WebImage\Models\Defs;

use WebImage\Config\Config;
use WebImage\Core\ImmutableDictionary;
use WebImage\Models\Security\RoleAccessInterface;

interface ModelDefinitionInterface
{
	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @param string
	 */
	public function setName(string $name): void;

	/**
	 * @return string
	 */
	public function getPluralName(): string;

	/**
	 * @param string $pluralName
	 */
	public function setPluralName(string $pluralName): void;

	/**
	 * @return string
	 */
	public function getFriendlyName(): string;

	/**
	 * @param string $friendly
	 */
	public function setFriendlyName(string $friendly): void;

	/**
	 * @return string
	 */
	public function getPluralFriendlyName(): string;

	/**
	 * @return array
	 */
	public function getSecurity(): array;

	/**
	 * @return PropertyDefinition[]
	 */
	public function getProperties(): array;

	/**
	 * @param string $name
	 * @return PropertyDefinition|null
	 */
	public function getProperty(string $name): ?PropertyDefinition;

	/**
	 * @return Config|null
	 */
	public function getConfig(): ?Config;

	/**
	 * @param RoleAccessInterface $access
	 */
	public function addSecurity(RoleAccessInterface $access): void;

	/**
	 * @param PropertyDefinition $property
	 */
	public function addProperty(PropertyDefinition $property): void;

	/**
	 * @param Config $config
	 */
	public function setConfig(Config $config): void;

	/**
	 * @return ?ImmutableDictionary|PropertyDefinition[]|<string propertyName, PropertyDefinition>
	 */
	public function getPrimaryKeys(): ?ImmutableDictionary;
}
