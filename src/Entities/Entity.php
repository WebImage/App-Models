<?php

namespace WebImage\Models\Entities;

use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Properties\InvalidPropertyException;
use WebImage\Models\Service\RepositoryAwareInterface;
use WebImage\Models\Service\RepositoryAwareTrait;
use WebImage\Models\Service\RepositoryInterface;

class Entity extends EntityStub implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;
	/** @var bool */
	private $isNew = true;
	/**
	 * @var EntityRefInterface
	 */
	private $entityRef;
	/**
	 * @var array
	 */
	private $associations = [];
	/**
	 * @var array
	 */
	private $extensions = [];

	public function __construct(string $model, RepositoryInterface $repo)
	{
		parent::__construct($model);
		$this->setRepository($repo);
	}

	/**
	 * Get the reference used by repository
	 *
	 * @return EntityRefInterface
	 */
	public function entityRef(): EntityRefInterface
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
	public function version()
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
	 * @param EntityRefInterface
	 */
	public function setEntityRef(EntityRefInterface $ref): void
	{
		$this->entityRef = $ref;
	}

	/**
	 * Set the version for the node
	 * @param long $version
	 */
	public function setVersion(long $version): void
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
	public function getAssociatedNodeRefs($assocQName=null)
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
		$this->changed(true);

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

	public function setPropertyValue(string $name, $value): void
	{
		$propDef = $this->getProperty($name)->getDef();

		if ($propDef->isVirtual() && $propDef->hasReference() && $value !== null) {
			$value = $this->normalizePropertyValue($propDef, $value);
		}

		parent::setPropertyValue($name, $value);
	}

	private function normalizePropertyValue(PropertyDefinition $propDef, $value)
	{
		if ($value instanceof EntityStub) return $value;

		$repo          = $this->getRepository();
		$entityService = $repo->getEntityService();
		$modelService  = $repo->getModelService();
		$targetModel   = $modelService->getModel($propDef->getReference()->getTargetModel());
		$entityRef     = $entityService->createReference($propDef->getReference()->getTargetModel());

		$primaryKeys = $targetModel->getDef()->getPrimaryKeys()->keys();

		if (is_string($value) || is_numeric($value)) {
			if (count($primaryKeys) == 1) {
				$entityRef->setPropertyValue($primaryKeys[0], $value);
			} else {
				throw new \Exception('Can only set simple values for models (' . $this->getModel() . ') with a single primary key');
			}
		} else if (is_array($value)) {
			$valueKeys = array_keys($value);
			if (count(array_diff($valueKeys, $primaryKeys)) > 0 || count(array_diff($primaryKeys, $valueKeys)) > 0) {
				throw new \Exception('Key count mismatch for model (' . $this->getModel() . ').  Expecting ' . implode(', ', $primaryKeys) . ', but found ' . implode($valueKeys));
			}

			foreach($primaryKeys as $primaryKey) {
				$entityRef->setPropertyValue($primaryKey, $value[$primaryKey]);
			}
		} else {
			throw new \Exception('Unsupported value for primary key');
		}

		return $entityRef;
	}

	private function getPrimaryKeys()
	{
		$primaryKeys = [];

		foreach($this->getProperties() as $property) {
			if ($property->getDef()->isPrimaryKey()) $primaryKeys[] = $property->getDef()->getName();
		}

		return $primaryKeys;
	}
}
