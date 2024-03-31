<?php

namespace WebImage\Models\Services;

use WebImage\Core\Dictionary;
#use WebImage\Node\Defs\DataType;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Properties\ValueMapper\ValueMapperInterface;
use WebImage\Models\Properties\ValueMapper\ValueMapResolver;
use WebImage\Models\Services\RepositoryAwareTrait;

/**
 * Manages DataTypes and InputElementDefs (and probably display elements)
 */
class DataTypeService implements DataTypeServiceInterface
{
	use RepositoryAwareTrait;
	private $NOMAPPER = 'NOMAPPER';
	private $valueMappers = [];
	/** @var ValueMapResolver */
	private $valueMapResolver;

	/**
	 * DataTypeService constructor.
	 */
	public function __construct(ValueMapResolver $valueMapResolver)
	{
		$this->valueMappers = new Dictionary();
		$this->valueMapResolver = $valueMapResolver;
	}

	/**
	 * @param $propertyType
	 *
	 * @return DataTypeDefinition
	 */
	public function getDefinition($propertyType): ?DataTypeDefinition
	{
		return $this->getRepository()->getDictionaryService()->getPropertyType($propertyType);
	}

	/**
	 * @inheritdoc
	 */
	public function getDefinitions(): array
	{
		return $this->getRepository()->getDictionaryService()->getPropertyTypes();
	}

	/**
	 * @inheritdoc
	 */
	public function valueForStorage(string $propertyTypeName, $value)/* PHP 8 : mixed*/
	{
		$mapper = $this->getPropertyValueMapper($propertyTypeName);

		return null === $mapper ? $value : $mapper->forStorage($value);
	}

	/**
	 * @inheritdoc
	 */
	public function valueForProperty(string $dataTypeName, $value)/* PHP 8 : mixed*/
	{
		$mapper = $this->getPropertyValueMapper($dataTypeName);

		return null === $mapper ? $value : $mapper->forProperty($value);
	}

	private function getPropertyValueMapper(string $propertyTypeName): ?ValueMapperInterface
	{
		if (!$this->valueMappers->has($propertyTypeName)) {
			$propertyType = $this->getDefinition($propertyTypeName);

			$valueMapper = $propertyType->getValueMapper();

			if (null === $valueMapper) {
				$this->valueMappers->set($propertyTypeName, $this->NOMAPPER);
				return null;
			}

			$this->valueMappers->set($propertyTypeName, $this->valueMapResolver->resolve($valueMapper));
		}

		$mapper = $this->valueMappers->get($propertyTypeName);

		return $mapper == $this->NOMAPPER ? null : $mapper;
	}
}
