<?php

namespace WebImage\Models\Properties\ValueMapper;

use WebImage\TypeResolver\TypeResolver;

class ValueMapResolver extends TypeResolver
{
	/**
	 * Typed resolver
	 * @param string $key
	 * @param null $configurator
	 * @return ValueMapperInterface
	 * @throws \Exception
	 */
	public function resolveTyped(string $key, $configurator = null): ValueMapperInterface
	{
		return parent::resolve($key, $configurator);
	}
}
