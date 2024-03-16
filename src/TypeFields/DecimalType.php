<?php

namespace WebImage\Models\TypeFields;

class DecimalType extends Type
{
	public function getTypeName()
	{
		return Type::DECIMAL;
	}

	public function getName()
	{
		return 'Decimal';
	}
}
