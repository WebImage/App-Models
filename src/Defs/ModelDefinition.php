<?php

namespace WebImage\Models\Defs;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;
use WebImage\Models\Security\RoleAccessInterface;

class ModelDefinition implements ModelDefinitionInterface
{
	private string $name           = ''; // Machine name
	private string $pluralName     = ''; // Machine name
	private string $friendly       = ''; // User-friendly  name of this type
	private string $pluralFriendly = ''; // User-friendly plural name of this type
	private array  $security       = [];
	private Config $config;
//	private $endpoint = '';
	private Dictionary $properties;

	/**
	 * TypeDefinition constructor.
	 * @param string $pluralName
	 */
	public function __construct(string $name, string $pluralName, string $friendlyName = '', string $pluralFriendlyName = '')
	{
		$this->name           = $name;
		$this->pluralName     = $pluralName;
		$this->friendly       = $friendlyName;
		$this->pluralFriendly = $pluralFriendlyName;

		$this->config     = new Config();
		$this->properties = new Dictionary();
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getPluralName(): string
	{
		return $this->pluralName;
	}

	/**
	 * @param string $pluralName
	 */
	public function setPluralName(string $pluralName): void
	{
		$this->pluralFriendly = $pluralName;
	}

	/**
	 * @return string
	 */
	public function getFriendlyName(): string
	{
		return strlen($this->friendly) > 0 ? $this->friendly : $this->pluralName;
	}

	/**
	 * @return string
	 */
	public function getPluralFriendlyName(): string
	{
		return strlen($this->pluralFriendly) > 0 ? $this->pluralFriendly : $this->getFriendlyName();
	}

	/**
	 * @param string $pluralFriendly
	 */
	public function setPluralFriendlyName(string $pluralFriendly): void
	{
		$this->pluralFriendly = $pluralFriendly;
	}

	/**
	 * @return array
	 */
	public function getSecurity(): array
	{
		return $this->security;
	}

	/**
	 * @return PropertyDefinition[]
	 */
	public function getProperties(): array
	{
		return array_values($this->properties->toArray());
	}

	/**
	 * @param string $name
	 * @return PropertyDefinition|null
	 */
	public function getProperty(string $name): ?PropertyDefinition
	{
		return $this->properties->get($name);
	}

	/**
	 * @return mixed
	 */
	public function getConfig(): ?Config
	{
		return $this->config;
	}

	/**
	 * @param mixed $config
	 */
	public function setConfig($config): void
	{
		$this->config = $config;
	}

//	public function getEndpoint() { return $this->endpoint; }

	/**
	 * @param RoleAccessInterface $access
	 */
	public function addSecurity(RoleAccessInterface $access): void
	{
		$this->security[] = $access;
	}

	/**
	 * @param PropertyDefinition $property
	 */
	public function addProperty(PropertyDefinition $property): void
	{
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * @param string $friendly
	 */
	public function setFriendlyName(string $friendly): void
	{
		$this->friendly = $friendly;
	}

	/**
	 * @inheritdoc
	 */
	public function getPrimaryKeys(): ImmutableDictionary
	{
		$keys = [];

		foreach ($this->getProperties() as $propDef) {
			if ($propDef->isPrimaryKey()) {
				$keys[$propDef->getName()] = $propDef;
			}
		}

		return new ImmutableDictionary($keys);
	}

//	public function setEndpoint(string $endpoint)
//	{
//		$this->endpoint = $endpoint;
//	}
}
