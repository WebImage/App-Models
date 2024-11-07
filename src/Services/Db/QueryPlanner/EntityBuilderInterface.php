<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Services\Db\PropertyLoaderInterface;
use WebImage\Models\Services\RepositoryInterface;

interface EntityBuilderInterface
{
	public function buildEntity(RepositoryInterface $repo, ConnectionManager $connectionManager/*, ModelDefinitionInterface $modelDef*/, EntityStub $entity, array $result, PropertyLoaderInterface $propertyLoader);
}