<?php

namespace WebImage\Models\Entities;

use Exception;
use WebImage\Core\ArrayHelper;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Exceptions\InvalidPropertyException;
use WebImage\Models\Services\ModelEntity;
use WebImage\Models\Services\RepositoryAwareInterface;
use WebImage\Models\Services\RepositoryAwareTrait;
use WebImage\Models\Services\RepositoryInterface;

class Entity extends EntityStub implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;

	private bool                $isNew     = true;
	private ?EntityRefInterface $entityRef = null;
	/**
	 * @var array
	 */
	private array $associations = [];
	/**
	 * @var array
	 */
	private array $extensions = [];

	public function __construct(string $model, RepositoryInterface $repo)
	{
		parent::__construct($model);
		$this->setRepository($repo);
	}

	/**
	 * Get the reference used by repository
	 *
	 * @return ?EntityRefInterface
	 */
	public function entityRef(): ?EntityRefInterface
	{
		return $this->entityRef;
	}

	/**
	 * Get the unique identifier for this node
	 *
	 * @return string
	 */
	public function uuid(): ?string
	{
		$ref = $this->entityRef();

		if (null === $ref) return null;

		return $ref->uuid();
	}

	/**
	 * Get the current version node
	 *
	 * @return ?string
	 */
	public function version(): ?string
	{
		$ref = $this->entityRef();

		if (null === $ref) return null;

		return $ref->version();
	}

//	/**
//	 * Get the parent node ref
//	 *
//	 * @return EntityRefInterface
//	 */
//	public function getParentEntityRef()
//	{
//		return $this->parentEntityRef;
//	}

	/**
	 * Set the node's repository reference
	 * @param EntityRefInterface $ref
	 */
	public function setEntityRef(EntityRefInterface $ref): void
	{
		$this->entityRef = $ref;
	}

	/**
	 * Set the version for the node
	 * @param int $version
	 */
	public function setVersion(int $version): void
	{
		$this->version = $version;
	}

	/**
	 * Save the node
	 *
	 * @return Entity
	 */
	public function save(): Entity
	{
		return $this->getRepository()->getEntityService()->save($this);
	}

	/**
	 * Delete the node
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		return $this->getRepository()->getEntityService()->delete($this);
	}

	/**
	 * Get the node refs for associated nodes
	 *
	 * @param $assocQName
	 *
	 * @return EntityRefInterface[]
	 */
	public function getAssociatedNodeRefs($assocQName = null)
	{
		throw new \RuntimeException('Not supported');
		$associations = $this->getRepository()->getEntityService()->getAssociatedNodeRefs($this, $assocQName);

		return $associations;
	}

	/**
	 * Associate a node with this node
	 *
	 * @param string $assocQName
	 * @param Node $dstNode
	 */
	public function addAssociation($assocQName, Node $dstNode)
	{
		throw new \RuntimeException('Not supported');
		$ref = $this->entityRef();

		$association = new NodeAssociation($assocQName, $this, $dstNode);
		$this->associations->add($association);
		$this->setHasChanged(true);

	}

	/**
	 * Remove a node association
	 *
	 * @param string $assocQName
	 * @param Node $dstNode
	 */
	public function removeAssociation($assocQName, Node $dstNode)
	{
		throw new \RuntimeException('Not supported');
		$ref = $this->entityRef();

		if (null !== $ref) {
			$this->getRepository()->getNodeService()->removeAssociation($assocQName, $this, $dstNode);
		}
	}

	/**
	 * Get all associations for this node
	 *
	 * @return array
	 */
	public function getAssociations(): array
	{
		return $this->associations;
	}

	/**
	 * @return bool
	 */
	public function isNew(): bool
	{
		return $this->isNew;
	}

	/**
	 * @param bool $isNew
	 */
	public function setIsNew(bool $isNew): void
	{
		$this->isNew = $isNew;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @throws Exception
	 */
	public function setPropertyValue(string $name, $value): void
	{
		$prop = $this->getProperty($name);
		if ($prop === null) {
			throw new InvalidPropertyException('Property ' . $name . ' does not exist on ' . $this->getModel());
		}
		$propDef = $prop->getDef();

		if ($propDef->isVirtual() && $propDef->hasReference() && $value !== null) {
			$value = $this->normalizeVirtualPropertyValue($propDef, $value);
		}

		parent::setPropertyValue($name, $value);
	}

	/**
	 * Check if all items in an array are of a certain type
	 * @param array $array
	 * @param string $type
	 * @return bool
	 */
	private function isArrayOfType(array $array, string $type): bool
	{
		return count($array) == count(array_filter($array, function ($item) use ($type) {
				return is_a($item, $type);
			}));
	}

	/**
	 * @throws Exception
	 */
	private function normalizeVirtualPropertyValue(PropertyDefinition $propDef, $value)
	{
		if ($value instanceof EntityStub) return $value;
		else if ($propDef->isMultiValued() && is_array($value) && count($value) > 0 && $this->isArrayOfType($value, EntityStub::class)) return $value;

		$repo          = $this->getRepository();
		$entityService = $repo->getEntityService();
		$modelService  = $repo->getModelService();
		$targetModel   = $modelService->getModel($propDef->getReference()->getTargetModel());
		$entityRef     = $entityService->createReference($propDef->getReference()->getTargetModel());
		$primaryKeys   = $targetModel->getDef()->getPrimaryKeys()->keys();

		if (is_string($value) || is_numeric($value)) {
			if (count($primaryKeys) == 1) {
				$entityRef->setPropertyValue($primaryKeys[0], $value);
			} else {
				throw new Exception('Can only set simple values for models (' . $this->getModel() . ') with a single primary key');
			}
		} else if (is_array($value)) {
			$valueKeys = array_keys($value);
			if (count(array_diff($valueKeys, $primaryKeys)) > 0 || count(array_diff($primaryKeys, $valueKeys)) > 0) {
				throw new Exception('Key count mismatch for model (' . $this->getModel() . ').  Expecting ' . implode(', ', $primaryKeys) . ', but found ' . implode($valueKeys));
			}

			foreach ($primaryKeys as $primaryKey) {
				$entityRef->setPropertyValue($primaryKey, $value[$primaryKey]);
			}
		} else if ($value instanceof ModelEntity) {
			throw new Exception('Model entity ' . get_class($value) . ' must be converted to standard ' . $value->getEntity()->getModel() . ' entity when saving ' . $propDef->getModel() . '.' . $propDef->getName() . '.');
		} else {
			throw new Exception('Unsupported value for primary key');
		}

		return $entityRef;
	}

	private function getPrimaryKeys(): array
	{
		$primaryKeys = [];

		foreach ($this->getProperties() as $property) {
			if ($property->getDef()->isPrimaryKey()) $primaryKeys[] = $property->getDef()->getName();
		}

		return $primaryKeys;
	}
}
