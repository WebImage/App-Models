<?php

namespace WebImage\Models\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebImage\Application\AbstractCommand;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Models\Compiler\YamlModelCompiler;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Entities\Model;
use WebImage\Models\Services\RepositoryInterface;

class GenerateModelClassesCommand extends AbstractCommand
{
	private ?string $baseNamespace;
	private ?string $outputDir;

	public function __construct(?string $name = null, ?string $baseNamespace = null, ?string $outputDir = null)
	{
		$this->baseNamespace = $baseNamespace;
		$this->outputDir     = $outputDir;
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('models:classes')
			 ->setDescription('Generate entity and repository classes from YAML model definitions')
			 ->setHelp('Generate strongly typed entity and repository classes based on YAML model files');

//		$this->addOption('models-dir', 'm', InputOption::VALUE_REQUIRED, 'Directory containing YAML model files', 'config/models');
		$this->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory for generated classes');
		$this->addOption('namespace', 'm', InputOption::VALUE_REQUIRED, 'Base namespace for generated classes');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force regeneration of all files (including non-base classes)');
		$this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch for changes and regenerate automatically');
		$this->addOption('watch-interval', 'i', InputOption::VALUE_REQUIRED, 'Watch interval in seconds', '1');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$force     = $input->getOption('force');
		/** @var RepositoryInterface $repo */
		$repo = $this->getContainer()->get(RepositoryInterface::class);

		$output->writeln("<info>Generating classes for " . count($repo->getModelService()->all()) . " models(s)...</info>");

		foreach($repo->getModelService()->all() as $model) {
			$output->writeln('Processing: ' . $model->getDef()->getName());
			$this->processModelDef($model->getDef(), $input, $output, $force);
		}

		$output->writeln("<info>Class generation complete!</info>");

		return 0;
	}

	private function getConfig(): Config
	{
		$app = $this->getContainer()->get(ApplicationInterface::class);
		return $app->getConfig();
	}

	private function processYamlFile(string $yamlFile, InputInterface $input, OutputInterface $output, bool $force): void
	{
		$modelDefinitions = $this->compiler->compileFile($yamlFile);

		foreach ($modelDefinitions as $modelDef) {
			$this->generateEntityClasses($modelDef, $input, $output, $force);
			$this->generateRepositoryClasses($modelDef, $input, $output, $force);
		}
	}

	private function processModelDef(ModelDefinition $modelDef, InputInterface $input, OutputInterface $output, bool $force): void
	{
		$this->generateEntityClasses($modelDef, $input, $output, $force);
		$this->generateRepositoryClasses($modelDef, $input, $output, $force);
	}

	private function generateEntityClasses(ModelDefinition $modelDef, InputInterface $input, OutputInterface $output, bool $force): void
	{
		$className = $this->getEntityClassName($modelDef);

		// Generate base entity class (always overwritten)
		$baseClassContent = $this->generateBaseEntityClass($modelDef);
		$baseClassPath    = $this->getBaseEntityPath($input, $modelDef);

		$this->writeFile($baseClassPath, $baseClassContent, true);
		$output->writeln("  Generated: {$baseClassPath}");

		// Generate entity class (only if it doesn't exist or force is true)
		$entityClassPath = $this->getEntityPath($input, $modelDef);
		if (!file_exists($entityClassPath) || $force) {
			$entityClassContent = $this->generateEntityClass($modelDef);
			$this->writeFile($entityClassPath, $entityClassContent, $force);
			$output->writeln("  Generated: {$entityClassPath}");
		} else {
			$output->writeln("  Skipped (exists): {$entityClassPath}");
		}
	}

	private function generateRepositoryClasses(ModelDefinition $modelDef, InputInterface $input, OutputInterface $output, bool $force): void
	{
		// Generate base repository class (always overwritten)
		$baseRepoContent = $this->generateBaseRepositoryClass($modelDef);
		$baseRepoPath = $this->getBaseRepositoryPath($input, $modelDef);
		$this->writeFile($baseRepoPath, $baseRepoContent, true);
		$output->writeln("  Generated: {$baseRepoPath}");

		// Generate repository class (only if it doesn't exist or force is true)
		$repoClassPath = $this->getRepositoryPath($input, $modelDef);
		if (!file_exists($repoClassPath) || $force) {
			$repoClassContent = $this->generateRepositoryClass($modelDef);
			$this->writeFile($repoClassPath, $repoClassContent, $force);
			$output->writeln("  Generated: {$repoClassPath}");
		} else {
			$output->writeln("  Skipped (exists): {$repoClassPath}");
		}
	}

	private function generateBaseEntityClass(ModelDefinition $modelDef): string
	{
		$className = $this->getEntityClassName($modelDef);
		$baseClassName = $className . 'Base';

		$namespace = $this->baseNamespace . '\\Entities\\Generated';

		$properties = [];
		$methods = [];

		foreach ($modelDef->getProperties() as $propDef) {
			$propertyMethods = $this->generatePropertyMethods($propDef);
			$methods = array_merge($methods, $propertyMethods);
		}

		$methodsString = implode("\n\n", $methods);

		return "<?php

/**
 * THIS FILE IS GENERATED - DO NOT MODIFY
 * Generated on: " . date('Y-m-d H:i:s') . "
 * Model: {$modelDef->getName()}
 */

namespace {$namespace};

use WebImage\\Models\\Services\\ModelEntity;
use WebImage\\Models\\Entities\\EntityStub;

abstract class {$baseClassName} extends ModelEntity
{
{$methodsString}
}";
	}

	private function generateEntityClass(ModelDefinition $modelDef): string
	{
		$className = $this->getEntityClassName($modelDef);
		$baseClassName = $className . 'Base';
		$namespace = $this->baseNamespace . '\\Entities';
		$baseNamespace = $this->baseNamespace . '\\Entities\\Generated';

		return "<?php

namespace {$namespace};

use {$baseNamespace}\\{$baseClassName};

class {$className} extends {$baseClassName}
{
    // Add custom methods and overrides here
}";
	}

	private function generateBaseRepositoryClass(ModelDefinition $modelDef): string
	{
		$entityClassName = $this->getEntityClassName($modelDef);
		$repoClassName = $this->getRepositoryClassName($modelDef);
		$baseRepoClassName = $repoClassName . 'Base';
		$namespace = $this->baseNamespace . '\\Repositories\\Generated';
		$entityNamespace = $this->baseNamespace . '\\Entities';

		$modelName = $modelDef->getName();
		$primaryKeys = $modelDef->getPrimaryKeys()->keys();
		$primaryKeyType = count($primaryKeys) === 1 ? $this->getPhpType($modelDef->getProperty($primaryKeys[0])) : 'array';

		return "<?php

/**
 * THIS FILE IS GENERATED - DO NOT MODIFY
 * Generated on: " . date('Y-m-d H:i:s') . "
 * Model: {$modelDef->getName()}
 */

namespace {$namespace};

use WebImage\\Models\\Services\\ModelRepository;
use WebImage\\Models\\Services\\RepositoryInterface;
use WebImage\\Models\\Entities\\EntityStub;
use WebImage\\Core\\Collection;
use {$entityNamespace}\\{$entityClassName};

abstract class {$baseRepoClassName} extends ModelRepository
{
    public function __construct(RepositoryInterface \$repo)
    {
        parent::__construct(\$repo, '{$modelName}');
    }
    
    public function get({$primaryKeyType} \$id): ?{$entityClassName}
    {
        \$entity = \$this->query()->get(\$id);
        return \$entity === null ? null : \$this->entityToModel(\$entity);
    }
    
    public function create(): {$entityClassName}
    {
        return \$this->entityToModel(parent::createEntity());
    }
    
    public function save({$entityClassName} \$model): {$entityClassName}
    {
        \$entity = \$model->getEntity();
        if (!(\$entity instanceof \\WebImage\\Models\\Entities\\Entity)) {
            throw new \\InvalidArgumentException('Entity must be an instance of Entity');
        }
        
        return \$this->entityToModel(\$entity->save());
    }
    
    protected function entityToModel(EntityStub \$entity): {$entityClassName}
    {
        return new {$entityClassName}(\$entity);
    }
}";
	}

	private function generateRepositoryClass(ModelDefinition $modelDef): string
	{
		$repoClassName = $this->getRepositoryClassName($modelDef);
		$baseRepoClassName = $repoClassName . 'Base';
		$namespace = $this->baseNamespace . '\\Repositories';
		$baseNamespace = $this->baseNamespace . '\\Repositories\\Generated';

		return "<?php

namespace {$namespace};

use {$baseNamespace}\\{$baseRepoClassName};

class {$repoClassName} extends {$baseRepoClassName}
{
    // Add custom repository methods here
}";
	}

	private function generatePropertyMethods(PropertyDefinition $propDef): array
	{
		$methods = [];
		$propertyName = $propDef->getName();
		$methodName = ucfirst($propertyName);
		$phpType = $this->getPhpType($propDef);
		$isNullable = !$propDef->isRequired();
		$nullablePrefix = $isNullable ? '?' : '';

		// Getter
		$getter = "    public function get{$methodName}(): {$nullablePrefix}{$phpType}
    {
        return \$this->entity['{$propertyName}'];
    }";

		// Setter
		$setter = "    public function set{$methodName}({$nullablePrefix}{$phpType} \${$propertyName}): void
    {
        \$this->entity['{$propertyName}'] = \${$propertyName};
    }";

		$methods[] = $getter;
		$methods[] = $setter;

		return $methods;
	}

	private function getPhpType(PropertyDefinition $propDef): string
	{
		if ($propDef->isVirtual() && $propDef->hasReference()) {
			$targetModel = $propDef->getReference()->getTargetModel();
			$entityClassName = $this->modelNameToClassName($targetModel) . 'Entity';

			if ($propDef->isMultiValued()) {
				return 'array'; // Could be enhanced to use typed arrays in PHP 8+
			}

			return $this->baseNamespace . '\\Entities\\' . $entityClassName;
		}

		$dataType = $propDef->getDataType();

		if ($propDef->isMultiValued()) {
			return 'array';
		}

		switch($dataType) {
			case 'integer':
				return 'int';
			case 'decimal':
				return 'float';
			case 'boolean':
				return 'bool';
			case 'date':
			case 'datetime':
				return '\\DateTime';
			case 'string':
			case 'text':
				return 'string';
			default:
				return 'mixed';
		}
	}

	private function getEntityClassName(ModelDefinition $modelDef): string
	{
		return $this->modelNameToClassName($modelDef->getName()) . 'Entity';
	}

	private function getRepositoryClassName(ModelDefinition $modelDef): string
	{
		return $this->modelNameToClassName($modelDef->getName()) . 'Repository';
	}

	private function modelNameToClassName(string $modelName): string
	{
		return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $modelName)));
	}

	private function getEntityPath(InputInterface $input, ModelDefinition $modelDef): string
	{
		$className = $this->getEntityClassName($modelDef);
		return $this->getOutputDir($input) . '/Entities/' . $className . '.php';
	}

	private function getBaseEntityPath(InputInterface $input, ModelDefinition $modelDef): string
	{
		$className = $this->getEntityClassName($modelDef) . 'Base';

		return $this->getOutputDir($input) . '/Entities/Generated/' . $className . '.php';
	}

	private function getRepositoryPath(InputInterface $input, ModelDefinition $modelDef): string
	{
		$className = $this->getRepositoryClassName($modelDef);
		return $this->getOutputDir($input) . '/Repositories/' . $className . '.php';
	}

	private function getBaseRepositoryPath(InputInterface $input, ModelDefinition $modelDef): string
	{
		$className = $this->getRepositoryClassName($modelDef) . 'Base';

		return $this->getOutputDir($input) . '/Repositories/Generated/' . $className . '.php';
	}

	private function writeFile(string $path, string $content, bool $overwrite = false): void
	{
		$directory = dirname($path);

		if (!is_dir($directory)) {
			mkdir($directory, 0755, true);
		}

		if (!file_exists($path) || $overwrite) {
			file_put_contents($path, $content);
		}
	}

	/**
	 * Allow input arguments to override the default namespace.
	 * @param InputInterface $input
	 * @return string
	 */
	private function getNamespace(InputInterface $input): string
	{
		$namespace = $input->getOption('namespace');
		if (!empty($namespace)) return $namespace;

		return ($this->baseNamespace ?? 'App') . '\\Models';
	}

	/**
	 * Allow input arguments to override the default output directory.
	 * @param InputInterface $input
	 * @return string
	 */
	private function getOutputDir(InputInterface $input): string
	{
		$outputDir = $input->getOption('output-dir');
		if (!empty($outputDir)) return rtrim($outputDir, '/\\');

		/** @var ApplicationInterface $app */
		$app = $this->getContainer()->get(ApplicationInterface::class);

		return $this->outputDir ?? $app->getProjectPath() . '/src/Models';
	}
}