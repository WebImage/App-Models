<?php

namespace WebImage\Models\TypeFields;

class TextType extends Type
{
	public function getTypeName()
	{
		return Type::TEXT;
	}

	public function getName()
	{
		return 'Text';
	}
}
