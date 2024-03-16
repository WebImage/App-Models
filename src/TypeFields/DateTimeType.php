<?php

namespace WebImage\Models\TypeFields;

class DateTimeType extends Type
{
	public function getTypeName()
	{
		return Type::DATETIME;
	}

	public function getName()
	{
		return 'Date/Time';
	}
}
