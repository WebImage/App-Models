<?php

namespace WebImage\Models\Services\Db;

use League\Container\Container;
use League\Container\DefinitionContainerInterface;
use WebImage\Config\Config;
use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Db\ConnectionManager;
use WebImage\Event\EventManager;
use WebImage\Event\EventManagerInterface;
use WebImage\Models\Helpers\DictionaryPropertyTypeHelper;
use WebImage\Models\Helpers\DictionaryTypeHelper;
use WebImage\Models\Properties\ValueMapper\ValueMapResolver;
use WebImage\Models\Services\DataTypeService;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\DictionaryService;
use WebImage\Models\Services\EntityServiceInterface;
use WebImage\Models\Services\Repository;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Models\Services\ModelServiceInterface;
use WebImage\Models\Compiler\YamlModelCompiler;

class RepositoryServiceProvider extends AbstractServiceProvider
{
	protected array $provides = [
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
		$container->addShared(RepositoryInterface::class, function(
			EventManagerInterface $eventManager,
			EntityServiceInterface $entityService,
			ModelServiceInterface $modelService,
			DataTypeServiceInterface $dataTypeService
		) use ($container) {
			$dictService = $this->createDictionary();
			return new Repository($eventManager, $entityService, $modelService, $dictService, $dataTypeService);
		})->addArguments([
			EventManagerInterface::class,
			EntityServiceInterface::class,
			ModelServiceInterface::class,
			DataTypeServiceInterface::class
		]);
	}

	private function createDictionary(): DictionaryService
	{
		/** @var Config $config */
		$dict   = new DictionaryService();
		$config = $this->getApplicationConfig()->get('webimage/models', new Config());

		/**
		 * Add models to dictionary
		 */
		$modelFiles = $config->get('models');
		if ($modelFiles === null) {
			throw new \RuntimeException('Config at webimage/models.models must contain an array of model files to include');
		} else if ($modelFiles !== null && !is_array($modelFiles)) {
			throw new \RuntimeException('Config at webimage/models.models must be an array');
		}

		$vars = $config->get('variables');
		foreach($modelFiles as $modelFile) {
			$models = DictionaryTypeHelper::load($modelFile, $vars);
			foreach($models as $model) {
				$dict->addModelDefinition($model);
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
