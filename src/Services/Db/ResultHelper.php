<?php

namespace WebImage\Models\Services\Db;

use Exception;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Properties\MultiValueProperty;
use WebImage\Models\Properties\Property;
use WebImage\Models\Properties\PropertyInterface;
use WebImage\Models\Properties\SingleValuePropertyInterface;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\UnsupportedMultiColumnKeys;
use WebImage\Models\Services\UnsupportedMultiValueProperty;

class ResultHelper
{
	/**
	 * @var DataTypeServiceInterface
	 */
	private $dataTypeService;

	/**
	 * ResultHelper constructor.
	 * @param DataTypeServiceInterface $dataTypeService
	 */
	public function __construct(DataTypeServiceInterface $dataTypeService)
	{
		$this->dataTypeService = $dataTypeService;
	}

	/**
	 * Create a Property from a result row that can be added to a Node
	 * @param string $modelTableKey The base table for the property value (used to form the result alias)
	 * @param PropertyDefinition $propDef
	 * @param array $data A single result
	 * @return PropertyInterface
	 * @throws UnsupportedMultiColumnKeys
	 */
	public function createPropertyFromData(string $modelTableKey, PropertyDefinition $propDef, array $data): PropertyInterface
	{
		if ($propDef->isMultiValued()) {
			return $this->createMultiValuePropertyFromData($modelTableKey, $propDef, $data);
		}

		return $this->createSingleValuePropertyFromData($modelTableKey, $propDef, $data);
	}

	private function createMultiValuePropertyFromData(string $modelTableKey, PropertyDefinition $propDef, array $data): MultiValueProperty
	{
		$property = new MultiValueProperty();
		$property->setDef($propDef);

		return $property;
	}

	/**
	 * @throws UnsupportedMultiColumnKeys
	 * @throws Exception
	 */
	private function createSingleValuePropertyFromData(string $modelTableKey, PropertyDefinition $propDef, array $data): Property
	{
		$property = new Property();
		$property->setDef($propDef);

		$propType  = $this->dataTypeService->getDefinition($propDef->getDataType());
		$repo          = $this->dataTypeService->getRepository();
		$modelService  = $repo->getModelService();
		$propModelDef  = $modelService->getModel($propDef->getModel())->getDef();
		$propColumns   = TableHelper::getPropertyColumns($modelService, $propModelDef, $propDef->getName());

		if ($propDef->isVirtual() && $propDef->hasReference()) {
			$cardinality = PropertyReferenceHelper::getAssociationCardinality($this->dataTypeService->getRepository()->getModelService(), $propDef);

			/**
			 * @TODO If all values are NULL then do not add the property
			 */
			$targetModelName      = $propDef->getReference()->getTargetModel();
			$targetModel          = $modelService->getModel($targetModelName);
			$targetModelColumns   = TableHelper::getPropertiesColumns($modelService, $targetModel->getDef());
			$targetModelTableName = TableNameHelper::getTableNameFromDef($targetModel->getDef());
			$propTableAlias       = TableNameHelper::getPropertyTableAlias($propDef->getName(), $targetModelTableName);
			$refEntity            = $repo->getEntityService()->createReference($targetModelName);

			if (count($propColumns->getColumns()) != 1) throw new UnsupportedMultiColumnKeys('References with no columns or more than one columns are not currently supported');

			$allNULL = true;
			$value = $propType->isSimpleStorage() ? null : [];

			// Get data from foreign key
			foreach($propColumns->getColumns() as $propColumn) {
				$alias = TableNameHelper::getColumnNameAlias($modelTableKey, $propColumn->getName(), $propColumn->getDataTypeField()->getKey());
				if (array_key_exists($alias, $data)) {
					$value = $data[$alias];
					if ($value !== null) $allNULL = false;
				}
				$refEntity->setPropertyValue($propColumns->getReferencedProperty(), $value);
			}

			// Check if data from target table has been included in the result data
			foreach($targetModelColumns->getProperties() as $refPropName => $refPropColumns) {
				$refPropDef = $targetModel->getDef()->getProperty($refPropName);
				$refPropType = $this->dataTypeService->getDefinition($refPropDef->getDataType());

				foreach($refPropColumns->getColumns() as $refPropColumn) {
					$refColumnAlias = TableNameHelper::getColumnNameAlias($propTableAlias, $refPropColumn->getName());
					if (array_key_exists($refColumnAlias, $data)) {
						$allNULL = false;
						if ($refPropType->isSimpleStorage() || $refPropColumn->getDataTypeField()->getKey() === null) {
							$value = $data[$refColumnAlias];
						} else {
							$value[$refPropColumn->getDataTypeField()->getKey()] = $data[$refColumnAlias];
						}
						$refEntity->setPropertyValue($refPropName, $value);
					}
				}
			}

			// Only set property value to referenced Entity IF it has values, otherwise keep the property value as NULL
			if ($cardinality->isManyToOne()) {
				$property->setIsValueLoaded(true);
			}
			if (!$allNULL) {
				$property->setValue($refEntity);
			}
		} else {
			$property->setIsValueLoaded(true);

			/**
			 * Simple storage indicates a single column maps to a single property values
			 * Otherwise, the value is an associative array
			 */
			$value = $propType->isSimpleStorage() ? null : [];

			foreach($propColumns->getColumns() as $propColumn) {
				$alias = TableNameHelper::getColumnNameAlias($modelTableKey, $propColumn->getName(), $propColumn->getDataTypeField()->getKey());
				if ($propType->isSimpleStorage()) $value = array_key_exists($alias, $data) ? $data[$alias] : null;
				else {
					$value[$propColumn->getDataTypeField()->getKey()] = array_key_exists($alias, $data) ? $data[$alias] : null;
				}
			}

			// Map data storage value to property safe value
			$value = $this->dataTypeService->valueForProperty($propType->getName(), $value);

			$property->setValue($value);
		}

		return $property;
	}

	public function injectReferencedValuesFromData(PropertyInterface $property, array $data)
	{
		if (!($property instanceof SingleValuePropertyInterface)) throw new \RuntimeException('Unable to inject reference properties for ' . get_class($property));

		$propDef = $property->getDef();
		if (!$propDef->isVirtual() || !$propDef->hasReference()) return;

		$modelService  = $this->dataTypeService->getRepository()->getModelService();
		$refModelName = $propDef->getReference()->getTargetModel();
		$refModel = $modelService->getModel($refModelName);

		if ($propDef->getModel() !== 'degree' || $propDef->getName() !== 'type') return;

		$propTableAlias       = TableNameHelper::getColumnNameAlias($propDef->getName(), $refModelName);

		foreach (TableHelper::getPropertiesColumns($modelService, $refModel->getDef())->getProperties() as $propertiesColumn) {
			foreach ($propertiesColumn->getColumns() as $column) {
				$columnName  = TableNameHelper::getColumnName($propTableAlias, $column->getName(), $column->getDataTypeField()->getKey());
				$columnAlias = TableNameHelper::getColumnNameAlias($propTableAlias, $column->getName(), $column->getDataTypeField()->getKey());
				#$qb->addSelect(sprintf('%s AS %s', $columnName, $columnAlias));
//				echo $columnName . ' --- ' . $columnAlias . '<br>';
			}
		}
		die(__FILE__ . ':' . __LINE__ . PHP_EOL);
	}
}
