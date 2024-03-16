<?php

namespace WebImage\Models\TypeFields;

class BooleanType extends Type
{
	public function getTypeName()
	{
		return Type::BOOLEAN;
	}

	public function getName()
	{
		return 'Boolean';
	}
}
