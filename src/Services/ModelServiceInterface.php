<?php

namespace WebImage\Models\Services;

use WebImage\Models\Entities\Model;

interface ModelServiceInterface extends RepositoryAwareInterface
{
	/** @return Model[] */
	public function all();

	/**
	 * @param string $name The name of the model to retrieve
	 *
	 * @return Model|null
	 */
	public function getModel(string $name): ?Model;

	/**
	 * Create a new Tyype
	 *
	 * @param string $friendlyName The name that the user will see when working with this model
	 * @param string $pluralFriendlyName Pluralized version of friendly name
	 * @param string|null $qname A QName to use for this Model.  If left blank then the QName will be created automatically based on the friendly name provided - generally this is probably the best way to go.
	 *
	 * @return ?Model
	 */
	public function create(string $name, string $pluralName, string $friendlyName, string $pluralFriendlyName): ?Model;

	/**
	 * Save a Model
	 *
	 * @access public
	 * @param Model The Model to save
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
//	 * @param NodeType $sourceType
//	 * @param NodeType $targetType
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
//		NodeType $sourceType,
//		NodeType $targetType,
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

	/**
	 * @param string $qnameStr
	 * @param string $key
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $default
	 * @param bool $isMultiValued
	 * @param int $sortorder
	 * @param Config $config
	 *
	 * @return NodeTypePropertyDef
	 */
//	public function createPropertyDef(string $nodeTypeQName, string $key, string $name, string $dataType, bool $required = false, $default = null, bool $isMultiValued = false, int $sortorder = null, Config $config = null);
}
