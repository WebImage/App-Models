<?php

namespace WebImage\Models\Services\Db;

use Doctrine\DBAL\Types\Types;

class DoctrineTypeMap
{
	static $types = [
		'boolean' => Types::BOOLEAN,
		'date'    => Types::DATE_MUTABLE,
		'datetime' => Types::DATETIME_MUTABLE,
		'decimal' => Types::DECIMAL,
		'integer' => Types::INTEGER,
		'string'  => Types::STRING,
		'text'  => Types::TEXT,
		'virtual' => null // Do not map to anything so that a property does not get created
	];

	static $typeOptions = [];

	public static function getDoctrineType(string $type)
	{
		if (!in_array($type, static::getTypes())) throw new \RuntimeException('Missing doctrine type: ' . $type);

		return static::$types[$type];
	}

	public static function getTypes()
	{
		return array_keys(static::$types);
	}

	public static function hasType(string $type)
	{
		return in_array($type, self::getTypes());
	}

	public static function hasDoctrineType(string $type)
	{
		return static::hasType($type) && static::getDoctrineType($type) === null;
	}
}
