<?php

namespace WebImage\Models\TypeFields;

class DateType extends Type
{
	public function getTypeName()
	{
		return Type::DATE;
	}

	public function getName()
	{
		return 'Date';
	}
}
