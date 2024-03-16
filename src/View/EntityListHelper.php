<?php

namespace WebImage\Models\View;

use WebImage\Core\Collection;
use WebImage\Models\Entities\EntityStub;
use WebImage\View\AbstractHelper;
use WebImage\View\ViewManager;
use WebImage\View\ViewManagerAwareInterface;
use WebImage\View\ViewManagerAwareTrait;

class EntityListHelper extends AbstractHelper implements ViewManagerAwareInterface
{
	use ViewManagerAwareTrait;

	public function __invoke(Collection $entities)
	{
		$baseViewKey = 'partials/entity-list';
		$entityTypeViewKey = $this->getEntityTypeViewKey($entities);

		$views = [];
		if ($entityTypeViewKey === null) $views[] = $baseViewKey . '-empty';
		else $views[] = $baseViewKey . '-' . $entityTypeViewKey;
		$views[] = $baseViewKey;

		$foundView = $this->getViewManager()->getFactory()->create($views, ['entities' => $entities]);

		return $foundView === null ? 'Missing view for entities' : $foundView;
	}

	private function getEntityTypeViewKey(Collection $entities): ?string
	{
		$entityModels = $this->getEntityModels($entities);
		sort($entityModels);

		return count($entityModels) == 0 ? null : implode('-', $entityModels);
	}

	/**
	 * @param Collection|Entity[] $entities
	 */
	private function getEntityModels(Collection $entities)
	{
		$models = [];

		foreach($entities as $entity) {
			if (!is_object($entity) || (is_object($entity) && !($entity instanceof EntityStub))) continue;

			if (!in_array($entity->getModel(), $models)) $models[] = $entity->getModel();
		}

		return $models;
	}
}
