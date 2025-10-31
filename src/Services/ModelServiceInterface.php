<?php

namespace WebImage\Models\Services;

use Exception;
use WebImage\Config\Config;
use WebImage\Models\Entities\Model;

interface ModelServiceInterface extends RepositoryAwareInterface
{
	/** @return Model[] */
	public function all(): array;

	/**
	 * @param string $name The name of the model to retrieve
	 *
	 * @return Model|null
	 */
	public function getModel(string $name): ?Model;

	/**
	 * Create a new Model
	 *
	 * @param string $name
	 * @param string $pluralName
	 * @param string $friendlyName The name that the user will see when working with this model
	 * @param string $pluralFriendlyName Pluralized version of friendly name
	 * @return ?Model
	 */
	public function create(string $name, string $pluralName, string $friendlyName, string $pluralFriendlyName): ?Model;

	/**
	 * Save a Model
	 *
	 * @access public
	 * @param Model $model The Model to save
	 *
	 * @return Model Includes any modifications that were made as a result of the save
	 * @throws Exception
	 *
	 */
	public function save(Model $model): ?Model;

	/**
	 * Delete Model
	 *
	 * @param Model $model
	 */
	public function delete(Model $model): bool;

//	/**
//	 * @return ModelAssociation[]
//	 */
//	public function getAssociations();
//
//	/**
//	 * @param string $assocQName
//	 *
//	 * @return NodeTypeAssociation
//	 */
//	public function getAssociationByQName($assocQName);

//	/**
//	 * @param $friendlyName
//	 * @param NodeType $sourceModel
//	 * @param NodeType $targetModel
//	 * @param string|null $assocTypeQName
//	 * @param bool $allowDuplicates
//	 * @param int|null $sourceMin
//	 * @param int|null $sourceMax
//	 * @param bool $sourceStrict
//	 * @param int|null $targetMin
//	 * @param int|null $targetMax
//	 * @param bool $targetStrict
//	 *
//	 * @return NodeTypeAssociation
//	 */
//	public function createAssociation(
//		$friendlyName,
//		NodeType $sourceModel,
//		NodeType $targetModel,
//		$assocTypeQName = null,
//		$allowDuplicates = true,
//		$sourceMin = null,
//		$sourceMax = null,
//		$sourceStrict = false,
//		$targetMin = null,
//		$targetMax = null,
//		$targetStrict = false
//	);
//
//	/**
//	 * Save an association definition
//	 */
//	public function saveAssociation(NodeTypeAssociation $assoc);
//
//	/**
//	 * @param NodeTypeAssociation $association
//	 */
//	public function deleteAssociation(NodeTypeAssociation $association);

//	public function createNodeTypePropertyDef($qname_str, $key, $name, $type, $required, $default, $is_multi_valued, $sortorder, $config);

//	/**
//	 * @param string $qnameStr
//	 * @param string $key
//	 * @param string $name
//	 * @param string $type
//	 * @param bool $required
//	 * @param mixed $default
//	 * @param bool $isMultiValued
//	 * @param int $sortorder
//	 * @param Config $config
//	 *
//	 * @return NodeTypePropertyDef
//	 */
//	public function createPropertyDef(string $nodeTypeQName, string $key, string $name, string $dataType, bool $required = false, $default = null, bool $isMultiValued = false, int $sortorder = null, Config $config = null);
}
