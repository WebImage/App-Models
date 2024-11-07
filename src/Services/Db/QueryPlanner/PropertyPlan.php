<?php

namespace WebImage\Models\Services\Db\QueryPlanner;

use WebImage\Core\ArrayHelper;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\Property;
use WebImage\Models\Properties\PropertyInterface;
use WebImage\Models\Properties\SingleValuePropertyInterface;
use WebImage\Models\Services\Db\PropertyLoaderInterface;
use WebImage\Models\Services\EntityDebugger;
use WebImage\Models\Services\RepositoryInterface;

class PropertyPlan implements SelectQueryBuilderInterface, EntityBuilderInterface
{
	private string $model;
	private string $property;
	/**
	 * @var Column[]
	 */
	private array $columns = [];

	/**
	 * @param string $model
	 * @param string $property
	 * @param Column[] $columns
	 */
	public function __construct(string $model, string $property, array $columns)
	{
		$this->assertValidColumns($columns);
		$this->columns = $columns;
		$this->model = $model;
		$this->property = $property;
	}

	protected function assertValidColumns(array $columns)
	{
		ArrayHelper::assertItemTypes($columns, Column::class);
	}

	/**
	 * @return Column[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	public function buildSelectQuery(\WebImage\Db\QueryBuilder $builder): void
	{
		foreach($this->getColumns() as $column) {
			if ($column instanceof SelectQueryBuilderInterface) {
				$column->buildSelectQuery($builder);
				continue;
			}
			$builder->addSelect(sprintf('`%s`.`%s` as %s', $column->getTable(), $column->getColumn(), $column->getAlias()));
		}
	}

	/**
	 * @throws \Exception
	 */
	public function buildEntity(RepositoryInterface $repo, ConnectionManager $connectionManager/*, ModelDefinitionInterface $modelDef*/, EntityStub $entity, array $result, PropertyLoaderInterface $propertyLoader)
	{
		$modelDef = $repo->getModelService()->getModel($this->getModel())->getDef();
		$this->buildValue($repo, $connectionManager, $entity, $result, $propertyLoader);
//		$entity->addProperty($this->getProperty(), $property);
	}

//	protected function createProperty(ModelDefinitionInterface $modelDef): Property
//	{
//
//		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
//		$property = new Property();
//		$propDef = $modelDef->getProperty($this->getProperty());
//		$property->setDef($propDef);
//
//		return $property;
//	}

//$property->setValue($this->createValue($repo, $connectionManager, $modelDef, $result, $propertyLoader));
	protected function buildValue(RepositoryInterface $repo, ConnectionManager $connectionManager, EntityStub $entity/*, ModelDefinitionInterface $modelDef*/, array $result, PropertyLoaderInterface $propertyLoader)
	{
		if (count($this->getColumns()) > 1) {
			echo '<pre>';
			print_r($this);
			throw new \Exception('Invalid number of columns');
		}

		foreach($this->getColumns() as $column) {
			$value = $result[$column->getAlias()];
		}

		$property = $entity->getProperty($this->getBuildValueProperty());
		if ($property === null) {
			echo EntityDebugger::renderEntityAsHtml($entity) . '<br/>' . PHP_EOL;
			die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
			throw new \Exception('Property not found: ' . $this->getBuildValueProperty());
		}

		$property->setValue($value);
		$property->setIsValueLoaded(true);
	}

	protected function getBuildValueProperty(): string
	{
		return $this->getProperty();
	}
}