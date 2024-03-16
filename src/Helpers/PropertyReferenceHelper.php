<?php

namespace WebImage\Models\Helpers;

use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\PropertyReferenceCardinality;
use WebImage\Models\Service\ModelServiceInterface;

class PropertyReferenceHelper
{
	public static function getAssociationCardinality(ModelServiceInterface $modelService, PropertyDefinition $propDef): ?PropertyReferenceCardinality
	{
		$reference = $propDef->getReference();
		$refType   = $modelService->getModel($reference->getTargetModel());

		$cardinality = new PropertyReferenceCardinality();

		if ($refType === null) return null;

		if ($reference->getReverseProperty() === null) {
//			echo 'CHECK: ' . $propDef->getModel() . '.' . $propDef->getName() . ': ' . $reference->getTargetModel() . ' vs ' . $propDef->getModel() . PHP_EOL;
			$cardinality->setSourceCardinality(count($reference->getPath()) > 0 ? PropertyReferenceCardinality::CARDINALITY_MULTIPLE : PropertyReferenceCardinality::CARDINALITY_ONE);
			$cardinality->setTargetCardinality($propDef->isMultiValued() ? PropertyReferenceCardinality::CARDINALITY_MULTIPLE : PropertyReferenceCardinality::CARDINALITY_ONE);
			$cardinality->setDirection($reference->getTargetModel() == $propDef->getModel() ? PropertyReferenceCardinality::DIRECTION_SELF : PropertyReferenceCardinality::DIRECTION_UNI);
		} else {
			$refTypeProp = $refType->getDef()->getProperty($reference->getReverseProperty());

			if ($refTypeProp !== null) {
				$refTypePropRef = $refTypeProp->getReference();

				if ($refTypePropRef !== null) {
					#echo $propDef->getModel() . '.' . $propDef->getName() . ' - $refTypePropRef: ' . ($refTypePropRef===null ? 'NULL':'DEFINED') . PHP_EOL;
//					echo 'CHECK: ' . $propDef->getModel() . ' vs ' . $refTypePropRef->getTargetModel() . PHP_EOL;
					if ($refTypePropRef->getTargetModel() == $propDef->getModel()) {
						if ($refTypePropRef->getReverseProperty() === null || $refTypePropRef->getReverseProperty() == $propDef->getName()) {
							$cardinality->setSourceCardinality($refTypeProp->isMultiValued() ? PropertyReferenceCardinality::CARDINALITY_MULTIPLE : PropertyReferenceCardinality::CARDINALITY_ONE);
							$cardinality->setTargetCardinality($propDef->isMultiValued() ? PropertyReferenceCardinality::CARDINALITY_MULTIPLE : PropertyReferenceCardinality::CARDINALITY_ONE);
							$cardinality->setDirection($reference->getTargetModel() == $propDef->getModel() ? PropertyReferenceCardinality::DIRECTION_SELF : PropertyReferenceCardinality::DIRECTION_BI);
						}
					}
				}
			}
		}

		return $cardinality;
	}
}
