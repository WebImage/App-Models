<?php

/**
 * Takes a collection of entities and builds a WHERE clause
 */
namespace WebImage\Models\Services\Db\QueryPlanner;

interface WhereMatchInterface
{
	public function build();
}