<?php

namespace WebImage\Models\Services;

use WebImage\Core\ArrayHelper;
use WebImage\Core\Collection;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Entities\EntityReference;
use WebImage\Models\Entities\EntityStub;
use WebImage\Models\Properties\MultiValueProperty;
use WebImage\Models\Properties\MultiValuePropertyInterface;

class EntityDebugger {
	public static function renderEntitiesAsHtml(Collection $entities): string
	{
		$html = '';

		foreach($entities as $entity) {
			$html .= sprintf('<div style="border: 1px solid #e1e1e1; padding: 5px; margin: 5px 0;">%s</div>', self::renderEntityAsHtml($entity));
		}

		return $html;
	}

	public static function renderEntityAsHtml(EntityStub $entity): string
	{
		return self::recursivelyDumpAsHtml($entity);
	}

	private static function recursivelyDumpAsHtml(EntityStub $entity, $depth=0): string
	{
		$html = '';
		$html .= sprintf('<div style="margin-left: %dpx">', $depth * 10);
		if ($depth == 0) $html .= sprintf('<strong>%s</strong> (<span style="color: #3ac;">%s</span>)', $entity->getModel(), get_class($entity)) . '<br>';
		$html .= '<div style="margin-left: 10px;">';
		foreach($entity->getProperties() as $property) {
			$html .= '<div>';
			$html .= '- ';
			$html .= $property->getDef()->getName();
			if ($property instanceof MultiValuePropertyInterface) $html .= '[]';
			$html .= sprintf(' <span style="color: #3ac; font-style: italic;">(%s)</span>', $property->getDef()->getDataType());
			if ($property instanceof MultiValuePropertyInterface) {
				$html .= ' (multi-valued) = ' . count($property->getValues()) . ' values';
			} else {
				$html .= ' = ';
				if ($property->getValue() === null) {
					$html .= 'Not set';
				} else {
					$value = $property->getValue();
					if (is_numeric($value) || is_string($value)) {
						if (is_string($value) && strlen($value) > 100) {
							$value = substr($value, 0, 97) . '...';
						}
						$html .= ' ' . htmlentities($value);
					} else if ($property->getValue() instanceof EntityReference) {
						$html .= self::recursivelyDumpAsHtml($property->getValue(), $depth + 1);
					} else {
						if (is_array($property->getValue()) && ArrayHelper::isAssociative($property->getValue())) {
							$html .= 'array[' . implode(', ', array_keys($property->getValue())) . ']';
						} else {
							$html .= gettype($property->getValue());
						}
					}
				}
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
