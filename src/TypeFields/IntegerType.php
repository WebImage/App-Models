<?php

namespace WebImage\Models\TypeFields;

class IntegerType extends Type
{
	/**
	 * @inheritdoc
	 */
	public function getTypeName()
	{
		return Type::INTEGER;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return 'Integer';
	}
}
