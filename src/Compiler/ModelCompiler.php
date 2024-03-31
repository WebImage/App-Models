<?php

namespace WebImage\Models\Compiler;

use WebImage\Core\ArrayHelper;
use WebImage\Models\Defs\PropertyPathDefinition;
use WebImage\Models\Defs\PropertyReferenceDefinition;
use WebImage\Models\Defs\ModelDefinitionInterface;
use WebImage\Models\Parsers\PropertyDefinitionParser;
use WebImage\Models\Services\Db\DoctrineTypeMap;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Security\RoleAccessInterface;

class ModelCompiler
{
	/**
	 * Compile an associative array [type-name => [definition...]]
	 * @param array $modelDefsData
	 * @return ModelDefinition[]|array
	 */
	public function compile(array $modelDefsData): array
	{
		if (!ArrayHelper::isAssociative($modelDefsData)) throw new \RuntimeException('Only [modelName => def] model definitions are accepted at this time');

		$models = [];

		foreach($modelDefsData as $modelName => $def) {
			if (!array_key_exists('name', $def)) $def['name'] = $modelName;
			$models[] = self::compileModelData($def);
		}

		return $models;
	}

	private function compileModelData(array $struct): ModelDefinition
	{
		ArrayHelper::assertKeys($struct, 'model', ['name', 'properties'], ['plural', 'primaryKey', 'security', 'friendly', 'pluralFriendly', 'related']);

		$pluralName = $struct['plural'] ?? $struct['name'];
		$friendlyName = $struct['friendly'] ?? $struct['name'];
		$pluralFriendlyName = $struct['pluralFriendly'] ?? $friendlyName;

		$modelDef = new ModelDefinition($struct['name'], $pluralName, $friendlyName, $pluralFriendlyName);
		$this->addProperties($modelDef, $struct);
		$this->addSecurity($modelDef, $struct);

		return $modelDef;
	}

	/**
	 * Add properties from the struct to $modelDef
	 *
	 * @param ModelDefinition $modelDef
	 * @param array $struct
	 * @throws \Exception
	 */
	private function addProperties(ModelDefinition $modelDef, array $struct)
	{
		if (!array_key_exists('properties', $struct)) throw new \RuntimeException('Missing required properties');

		$skipAutoPrimaryKey = $this->doesPropertyInfoHaveValue($struct, 'primaryKey');
		$defaultPrimaryKey = 'id';

		foreach($struct['properties'] as $name => $propertyInfo) {
			list($name, $propertyInfo) = $this->normalizePropertyInfo($name, $propertyInfo);

			$propertyInfo['name'] = $name;

			if (substr($propertyInfo['type'], -2) == '[]') {
				$propertyInfo['type'] = substr($propertyInfo['type'], 0, -2);
				$propertyInfo['multiple'] = true;
			}

			ArrayHelper::assertKeys($propertyInfo, 'properties['.$name.']', ['name', 'type'], ['comment', 'default', 'generationStrategy', 'multiple', 'primaryKey', 'required', 'reference', 'security', 'size', 'size2']);

			$propertyDef = new PropertyDefinition($modelDef->getName(), $propertyInfo['name'], $propertyInfo['type']);//, DoctrineTypeMap::hasDoctrineType($propertyInfo['type']));

			$this->addPropertyReference($modelDef, $propertyDef, $propertyInfo);

			// Whether property can have multiple values
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'multiple') && $this->assertBoolean($propertyInfo['multiple']) === true) $propertyDef->setIsMultiValued($propertyInfo['multiple']);
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'security')) $this->addPropertyDefSecurity($propertyDef, $name, $propertyInfo['security']);
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'comment')) $propertyDef->setComment($this->assertString($propertyInfo['comment']));
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'default')) $propertyDef->setDefault($propertyInfo['default']);
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'required')) $propertyDef->setIsRequired($this->assertBoolean($propertyInfo['required']));
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'size')) {
				$propertyDef->setSize($this->assertInteger($propertyInfo['size']));
				if ($this->doesPropertyInfoHaveValue($propertyInfo, 'size2')) $propertyDef->setSize2($this->assertInteger($propertyInfo['size2']));
			} else if ($this->doesPropertyInfoHaveValue($propertyInfo, 'size2')) {
				throw new \RuntimeException('Size must be set if size2 is used');
			}
			if ($this->doesPropertyInfoHaveValue($propertyInfo, 'primaryKey')) {
				$skipAutoPrimaryKey = $propertyInfo['name'] == $defaultPrimaryKey && $propertyInfo['primaryKey'] === false; // If $defaultPrimaryKey is explicitly set to FALSE, then do not auto-generate a primary key - only applies when no other primary key is set
				$propertyDef->setIsPrimaryKey($propertyInfo['primaryKey']);
			};

			if (array_key_exists('generationStrategy', $propertyInfo)) $propertyDef->setGenerationStrategy($propertyInfo['generationStrategy']);

			$modelDef->addProperty($propertyDef);
		}

		// Add global primary key
		if ($this->doesPropertyInfoHaveValue($struct, 'primaryKey')) {
			if (count($modelDef->getPrimaryKeys())) {
				throw new \RuntimeException($modelDef->getName() . ' cannot have a primary key if the properties already set a primary key');
			}
			if (ArrayHelper::isAssociative($struct['primaryKey'])) {
				throw new \RuntimeException($modelDef->getName() . '\'s primaryKey must be a list of strings');
			}
			foreach($struct['primaryKey'] as $ix => $primaryKey) {
				if (!is_string($primaryKey)) throw new \RuntimeException($modelDef->getName() . ' primary key at index ' . $ix . ' must be a string');
				$idProperty = $modelDef->getProperty($primaryKey);
				if ($idProperty === null) throw new \RuntimeException($modelDef->getName() . ' set ' . $primaryKey . ' as a primary key, but no such property exists on the model');
				$idProperty->setIsPrimaryKey(true);
			}
		}

		// Auto create primary key if it was not set above
		if (!$skipAutoPrimaryKey && $modelDef->getPrimaryKeys()->count() == 0 && $modelDef->getProperty($defaultPrimaryKey) !== null) {
			$idProperty = $modelDef->getProperty($defaultPrimaryKey);
			$idProperty->setIsPrimaryKey(true);
			$idProperty->setGenerationStrategy('auto');
		}
	}

	/**
	 * Check for key existance and non-NULL value
	 *
	 * @param array $propertyInfo
	 * @param string $key
	 * @return bool
	 */
	private function doesPropertyInfoHaveValue(array $propertyInfo, string $key): bool
	{
		return array_key_exists($key, $propertyInfo) && $propertyInfo[$key] !== null;
	}

	/**
	 * Add security definition to type
	 *
	 * @param ModelDefinition $modelType
	 * @param array $struct
	 */
	private function addSecurity(ModelDefinition $modelType, array $struct)
	{
		if (!array_key_exists('security', $struct)) return;
		$security = $struct['security'];

		if (ArrayHelper::isAssociative($security)) throw new \RuntimeException('Security must be an array of arrays');

		foreach($struct['security'] as $ix => $security) {
			ArrayHelper::assertKeys($security, 'security['.$ix.']', ['role', 'create', 'read', 'update', 'delete']);

			$modelType->addSecurity(new RoleAccessInterface($security['role'], $security['create'], $security['read'], $security['update'], $security['delete']));
		}
	}

	/**
	 * Ensure that propertyInfo has base set of default values
	 * @param string $name
	 * @param $propertyInfo
	 * @return array
	 * @throws \Exception
	 */
	private function normalizePropertyInfo(string $name, /* mixed */ $propertyInfo)
	{
		if (is_string($propertyInfo)) $propertyInfo = $this->createPropertyInfoFromString($name, $propertyInfo);
		else if ($propertyInfo === null) throw new \InvalidArgumentException('Normalizing property info for ' . $name . '.  Cannot be NULL');

		$defaults = [
			'type' => null,
			'reference' => $this->normalizePropertyReferenceType([], $name, $propertyInfo)
		];

		$propertyInfo = array_merge($defaults, $propertyInfo);

		// @ at the beginning of a property name indicates a primary key
		if (substr($name, 0, 1) == '@') {
			$name = substr($name, 1);
			if (array_key_exists('primaryKey', $propertyInfo) && $propertyInfo['primaryKey'] !== null) {
				throw new \Exception($name . ' should not have a primaryKey property if name uses the @ property name prefix, which already indicates a primary key');
			}
			$propertyInfo['primaryKey'] = true;
		}

		return [$name, $propertyInfo];
	}

	private function addPropertyReference(ModelDefinitionInterface $typeDef, PropertyDefinition $propDef, array $propertyInfo)
	{
		$referenceData = $this->normalizePropertyReferenceType($propertyInfo['reference'], $propDef->getName(), $propertyInfo);

		ArrayHelper::assertKeys($propertyInfo['reference'], 'properties['.$propDef->getName().']', ['targetType'], ['path', 'selectProperty', 'reverseProperty', 'multiple']);

		if ($propertyInfo['reference']['targetType'] === null) return;

		$propertyReference = new PropertyReferenceDefinition($referenceData['targetType']);

		if ($referenceData['reverseProperty'] !== null) $propertyReference->setReverseProperty($referenceData['reverseProperty']);
		if ($referenceData['multiple'] !== null) $propDef->setIsMultiValued($referenceData['multiple']);
		if ($referenceData['selectProperty'] !== null) $propertyReference->setSelectProperty($referenceData['selectProperty']);

		for($i=0; $i < count($referenceData['path']); $i ++) {
			$path = $referenceData['path'][$i];
			ArrayHelper::assertKeys($path, 'properties['.$propDef->getName().'][' . $i . ']', ['type', 'property', 'forwardProperty']);
			$propertyReference->addPath(new PropertyPathDefinition($path['type'], $path['property'], $path['forwardProperty']));
		}

		$propDef->setReference($propertyReference);
	}

	private function normalizePropertyReferenceType(/* mixed */ $data=array(), string $name, array $propertyInfo)
	{
		$defaults = [
			'targetType' => null,
			'reverseProperty' => null,
			'path' => [],
			'multiple' => null,
			'selectProperty' => null
		];

		if (is_string($data)) {
			$data = [
				'targetType' => $data
			];
		}

		$data = array_merge($defaults, $data);

		// [] At end of type indicates "multi-value"
		if (substr($data['targetType'], -2) == '[]') {
			$data['targetType'] = substr($data['targetType'], 0, -2);
			$data['multiple']   = true;
		}

		return $data;
	}

	private function createPropertyInfoFromString(string $propertyName, string $property)
	{
		$parser = new PropertyDefinitionParser();

		try {
			$parsed = $parser->parse($property);
		} catch (\Exception $e) {
			throw new \Exception('Failed to parse ' . $propertyName . ': ' . $property . ': ' . $e->getMessage());
		}

		return $parsed;
	}

	private function addPropertyDefSecurity(PropertyDefinition $def, $propertyIx, array $accessLevels)
	{
		if (ArrayHelper::isAssociative($accessLevels)) throw new \RuntimeException('properties['.$propertyIx.'] must be an array of arrays');

		foreach($accessLevels as $ix => $access) {
			ArrayHelper::assertKeys($access, 'properties['.$propertyIx.'].access['.$ix.']', ['role', 'create', 'read', 'update', 'delete']);

			$def->addSecurity(new RoleAccessInterface($access['role'], $access['create'], $access['read'], $access['update'], $access['delete']));
		}
	}

	private function assertString(string $string): string { return $string; }
	private function assertBoolean(bool $flag): bool { return $flag; }
	private function assertInteger(int $integer): int { return $integer; }
}
