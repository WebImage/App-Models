<?php

namespace WebImage\Models\Properties\ValueMapper;

use WebImage\Core\Dictionary;

class BooleanMapper implements ValueMapperInterface
{
	public function forStorage($value)
	{
		return $value === true || $value == 1 ? 1 : 0;
	}

	public function forProperty($value)
	{
		return $value == 1 || $value === true;
	}
}
