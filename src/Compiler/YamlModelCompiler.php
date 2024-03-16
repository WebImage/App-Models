<?php

namespace WebImage\Models\Compiler;

use Symfony\Component\Yaml\Yaml;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Service\Db\DoctrineTypeMap;

class YamlModelCompiler extends ModelCompiler
{
	/**
	 * @param $file
	 * @return ModelDefinition[]|array
	 */
	public function compileFile($file): array
	{
		return $this->compile( Yaml::parseFile($file) );
	}
}
