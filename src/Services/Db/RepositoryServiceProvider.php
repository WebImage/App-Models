<?php

namespace WebImage\Models\Services\Db;

use League\Container\DefinitionContainerInterface;
use WebImage\Config\Config;
use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Db\ConnectionManager;
use WebImage\Event\EventManagerInterface;
use WebImage\Models\Helpers\DictionaryPropertyTypeHelper;
use WebImage\Models\Properties\ValueMapper\ValueMapResolver;
use WebImage\Models\Providers\ModelDefinitionProviderInterface;
use WebImage\Models\Services\DataTypeService;
use WebImage\Models\Services\DataTypeServiceInterface;
use WebImage\Models\Services\DictionaryService;
use WebImage\Models\Services\EntityServiceInterface;
use WebImage\Models\Services\Repository;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Models\Services\ModelServiceInterface;

class RepositoryServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        RepositoryInterface::class,
        EntityServiceInterface::class,
        ModelServiceInterface::class,
        DataTypeServiceInterface::class
    ];

    public function register(): void
    {
        $container = $this->getContainer();
        $this->registerRepository($container);
        $this->registerEntityService($container);
        $this->registerModelService($container);
        $this->registerDataTypeService($container);
    }

    private function registerRepository(DefinitionContainerInterface $container): void
    {
        $container->addShared(RepositoryInterface::class, function(
            EventManagerInterface $eventManager,
            EntityServiceInterface $entityService,
            ModelServiceInterface $modelService,
            DataTypeServiceInterface $dataTypeService,
            ModelDefinitionProviderInterface $modelDefinitionProvider
        ) {
            $dictService = $this->createDictionary($modelDefinitionProvider);
            return new Repository($eventManager, $entityService, $modelService, $dictService, $dataTypeService);
        })->addArguments([
            EventManagerInterface::class,
            EntityServiceInterface::class,
            ModelServiceInterface::class,
            DataTypeServiceInterface::class,
            ModelDefinitionProviderInterface::class
        ]);
    }

    private function createDictionary(ModelDefinitionProviderInterface $provider): DictionaryService
    {
        $dict   = new DictionaryService();
        $config = $this->getApplicationConfig()->get('webimage/models', new Config());

        /**
         * Add models to dictionary from provider
         */
        foreach ($provider->getAllModelDefinitions() as $model) {
            $dict->addModelDefinition($model);
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
            ->addArgument(ConnectionManager::class);
    }

    private function registerModelService(DefinitionContainerInterface $container)
    {
        $container
            ->addShared(ModelServiceInterface::class, ModelService::class)
            ->addArgument(ConnectionManager::class);
    }

    private function registerDataTypeService(DefinitionContainerInterface $container)
    {
        $container->addShared(DataTypeServiceInterface::class, DataTypeService::class)
            ->addArgument(ValueMapResolver::class);
    }
}
