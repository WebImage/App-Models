<?php

namespace WebImage\Models\Properties\ValueMapper;

class DoubleMapper implements ValueMapperInterface
{
	public function forStorage($value)
	{
		return $value;
	}

	public function forProperty($value)
	{
		return doubleval($value);
	}
}
