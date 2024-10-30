<?php

namespace WebImage\Models\Defs;

use WebImage\Config\Config;
use WebImage\Config\CreatableFromConfiguratorInterface;
use WebImage\Core\Dictionary;

class DataTypeField {
	/** @var string One of WebImage\Node\Types\Type::CONSTANTS */
	private $type;
	/** @var string */
	private $key;
	/** @var string */
	private $name;
	/** @var Dictionary */
	private Dictionary $options;

	/**
	 * DataTypeModelField constructor.
	 *
	 * @param string $type
	 * @param string|null $key
	 * @param string|null $name
	 * @param Dictionary|null $options
	 */
	public function __construct(string $type, string $key=null, string $name=null, Dictionary $options=null)
	{
		$this->type = $type;
		$this->key = $key;
		$this->name = $name;
		$this->options = null === $options ? new Dictionary() : $options;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * An internal key to be used for generating classes and storage structures
	 * @return string|null
	 */
	public function getKey(): ?string
	{
		return $this->key;
	}

	/**
	 * A user friendly name
	 * @return string|null
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @return Dictionary
	 */
	public function getOptions(): Dictionary
	{
		return $this->options;
	}

	public static function createFromConfig(Config $config)
	{
		return new static(
			$config->get('type'),
			$config->get('key'),
			$config->get('name'),
			$config->get('options')
		);
	}
}
