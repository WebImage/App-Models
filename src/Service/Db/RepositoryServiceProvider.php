<?php

namespace WebImage\Models\Service\Db;

use League\Container\Container;
use League\Container\DefinitionContainerInterface;
use WebImage\Config\Config;
use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Db\ConnectionManager;
use WebImage\Models\Helpers\DictionaryPropertyTypeHelper;
use WebImage\Models\Helpers\DictionaryTypeHelper;
use WebImage\Models\Properties\ValueMapper\ValueMapResolver;
use WebImage\Models\Service\DataTypeService;
use WebImage\Models\Service\DataTypeServiceInterface;
use WebImage\Models\Service\DictionaryService;
use WebImage\Models\Service\EntityServiceInterface;
use WebImage\Models\Service\Repository;
use WebImage\Models\Service\RepositoryInterface;
use WebImage\Models\Service\ModelServiceInterface;
use WebImage\Models\Compiler\YamlModelCompiler;

class RepositoryServiceProvider extends AbstractServiceProvider
{
	protected $provides = [
		RepositoryInterface::class,
		EntityServiceInterface::class,
		ModelServiceInterface::class,
		DataTypeServiceInterface::class,
		TableNameHelper::class
	];

	public function register(): void
	{
		$container = $this->getContainer();
		$this->registerRepository($container);
		$this->registerEntityService($container);
		$this->registerModelService($container);
		$this->registerDataTypeService($container);

		$container->addShared(TableNameHelper::class, TableNameHelper::class);
	}

	private function registerRepository(DefinitionContainerInterface $container)
	{
		$container->addShared(RepositoryInterface::class, function(EntityServiceInterface $entityService, ModelServiceInterface $modelService, DataTypeServiceInterface $dataTypeService) use ($container) {
			$dictService = $this->createDictionary($container);

			return new Repository($entityService, $modelService, $dictService, $dataTypeService);
		})->addArguments([
			EntityServiceInterface::class,
			ModelServiceInterface::class,
			DataTypeServiceInterface::class
		]);
	}

	private function createDictionary(DefinitionContainerInterface $container): DictionaryService
	{
		/** @var Config $config */
		$dict   = new DictionaryService();
		$config = $this->getApplicationConfig()->get('webimage/models', new Config());

		/**
		 * Add models to dictionary
		 */
		$modelFiles = $config->get('models');
		$modelFiles = is_array($modelFiles) ? $modelFiles : [$modelFiles];

		foreach($modelFiles as $modelFile) {
			foreach(glob($modelFile) as $file) {
				$models = DictionaryTypeHelper::load($file);
				foreach($models as $model) {
					$dict->addModel($model);
				}
			}
		}

		/**
		 * Add property models
		 */
		$propertyTypesData = $config->get('propertyTypes', new Config());
		$propertyTypes = DictionaryPropertyTypeHelper::load($propertyTypesData);

		foreach($propertyTypes as $propertyType) {
			$dict->addPropertyType($propertyType);
		}

		/**
		 * Add property type aliases
		 */
		$propTypeAliases = $config->get('propertyTypeAliases', []);
		foreach($propTypeAliases as $alias => $propType) {
			$dict->setPropertyTypeAlias($alias, $propType);
		}

		return $dict;
	}

	private function registerEntityService(DefinitionContainerInterface $container)
	{
		$container->addShared(EntityServiceInterface::class, EntityService::class)
			->addArgument(ConnectionManager::class)
			->addArgument(TableNameHelper::class);
	}

	private function registerModelService(DefinitionContainerInterface $container)
	{
		$container
			->addShared(ModelServiceInterface::class, ModelService::class)
			->addArgument(ConnectionManager::class)
			->addArgument(TableNameHelper::class);
	}

	private function registerDataTypeService(DefinitionContainerInterface $container)
	{
		$container->addShared(DataTypeServiceInterface::class, DataTypeService::class)
			->addArgument(ValueMapResolver::class);
	}
}
