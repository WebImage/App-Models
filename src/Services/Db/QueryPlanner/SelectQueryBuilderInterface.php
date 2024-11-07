<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Services\Db\EntityCollection;
use WebImage\Models\Services\Db\LazyEntity;

interface SelectQueryBuilderInterface {
	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void;
}