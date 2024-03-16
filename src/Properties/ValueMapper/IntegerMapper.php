<?php

namespace WebImage\Models\Properties\ValueMapper;

class IntegerMapper implements ValueMapperInterface
{
	public function forStorage($value)
	{
		return $value;
	}

	public function forProperty($value)
	{
		return intval($value);
	}
}
