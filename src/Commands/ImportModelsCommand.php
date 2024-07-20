<?php

namespace WebImage\Models\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WebImage\Application\AbstractCommand;
use WebImage\Application\ApplicationInterface;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Defs\PropertyPathDefinition;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Entities\Model;
use WebImage\Models\Helpers\PropertyReferenceHelper;
use WebImage\Models\Services\ModelServiceInterface;
use WebImage\Models\Services\RepositoryInterface;
use WebImage\Models\Compiler\YamlModelCompiler;

class ImportModelsCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('models:import')
			->setDescription('Imports models from configured \'webimage/models\' config key')
			->setHelp('Import models from YAML files');
		$this->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch the types file for updates and automatically import new types');
		$this->addOption('limit-model', 'm', InputOption::VALUE_REQUIRED, 'Limit the model(s) to be dumped.  Specify multiple models comma delimited');
		$this->addOption('debug', 'd', InputOption::VALUE_NONE, 'Dumps the structure of an import without importing any actual values');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (($modelsFile = $this->getModelsFile($output)) === null) return 0;

		if ($input->getOption('watch')) {
			$this->watch($input, $output, $modelsFile);
		} else {
			$this->importModels($input, $output, $modelsFile);
		}

		return 0;
	}

	protected function watch(InputInterface $input, OutputInterface $output, string $modelsFile)
	{
		$lastUpdated = null;

		while (true) {
			clearstatcache();
			$modified = filemtime($modelsFile);
			if ($lastUpdated === null || $lastUpdated != $modified) {
				$this->importModels($input, $output, $modelsFile);
				$lastUpdated = $modified;
			}
			sleep(1);
		}
	}

	private function importModels(InputInterface $input, OutputInterface $output, string $modelsFile)
	{
		if (($modelDefs = $this->getModels($modelsFile, $output)) === null) return;

		/** @var RepositoryInterface $repo */
		$repo = $this->getContainer()->get(RepositoryInterface::class);

		$saveModels = [];

		// First, create all model instances so that they can reference one another one save
		foreach($modelDefs as $modelDef) {

			$model = $repo->getModelService()->getModel($modelDef->getName());

			if ($model === null) {
				$model = $repo->getModelService()->create($modelDef->getName(), $modelDef->getPluralName(), $modelDef->getFriendlyName(), $modelDef->getFriendlyName());
			}

			$model->setDef($modelDef);

			$saveModels[] = $model;
		}

		if ($input->getOption('debug')) {
			$this->displayStructure($input, $output, $repo->getModelService(), $saveModels);
		} else {
			foreach ($saveModels as $saveModel) {
				$saveModel->save();
			}
			$output->writeln(date('Y-m-d H:i:s') . ' Updated');
		}
	}

	/**
	 * @param OutputInterface $output
	 * @param ModelServiceInterface $modelService
	 * @param Model[] $models
	 */
	public function displayStructure(InputInterface $input, OutputInterface $output, ModelServiceInterface $modelService, array $models)
	{
		$table = new Table($output);
		$table->setHeaders(['Property', 'Type', '# Values', 'Reference', 'Relationship', 'Comment']);
		$first = true;

		foreach ($models as $model) {
			$modelDef = $model->getDef();

			if (!$first) $table->addRow(new TableSeparator());

			$first = false;
			$table->addRow([$modelDef->getPluralName()]);
			$table->addRow(new TableSeparator());

			foreach ($modelDef->getProperties() as $propDef) {
				$success = strlen($propDef->getComment()) == 0 || $this->getRelationshipDescription($modelService, $propDef) == $propDef->getComment();
//				if ($success) continue;

				$table->addRow([
//					'  - ' . $propDef->getName(),
//					'  - ' . $modelDef->getName() . '.' . $propDef->getName(),
					'  .' . $propDef->getName(),
					$propDef->getDataType(),
					$propDef->isMultiValued() ? 'Multi' : 'Single',
					$this->getReferenceDescription($propDef),
					$this->getRelationshipDescription($modelService, $propDef),
					/*$success ? 'SUCCESS' : */$propDef->getComment()
				]);
			}
		}

		$table->render();
	}

	private function getReferenceDescription(PropertyDefinition $propDef)
	{
		if (!$propDef->hasReference()) return '';

		$desc = $propDef->getReference()->getTargetModel();

		if ($propDef->getReference()->getReverseProperty() !== null) $desc .= '.' . $propDef->getReference()->getReverseProperty();

		if (count($propDef->getReference()->getPath()) > 0) {

			$desc .= ' (via ';
			$desc .= implode(', ', array_map(function(PropertyPathDefinition $path) {
				$desc = $path->getTargetModel();
				if ($path->getProperty() !== null) $desc .= '.' . $path->getProperty();
				if ($path->getForwardProperty() !== null) $desc .= ' on ' . $path->getTargetModel() . '.' . $path->getForwardProperty();
				return $desc;
			}, $propDef->getReference()->getPath()));
			$desc .= ')';
		}

		if ($propDef->getReference()->getSelectProperty() !== null) {
			$desc .= ' @ ' . $propDef->getReference()->getSelectProperty();
		}

		return $desc;
	}

	private function getRelationshipDescription(ModelServiceInterface $modelService, PropertyDefinition $propDef)
	{
		if (!$propDef->hasReference()) return '';

		return (string) PropertyReferenceHelper::getAssociationCardinality($modelService, $propDef);
	}

	#private function fetchReversePropertyRelationship($modelService, )

	#private function hasReverseRelationship(ModelServiceInterface $typeService, PropertyDefinition)

	/**
	 * @param string $modelsFile
	 * @param OutputInterface $output
	 * @return ModelDefinition[]
	 */
	private function getModels(string $modelsFile): array
	{
		$importer = new YamlModelCompiler();

		return $importer->compileFile($modelsFile);
	}

	private function getModelsFile(OutputInterface $output): ?string
	{
		$config = $this->getApp()->getConfig()->get('webimage/models');
		if (null === $config) {
			$output->writeln('Missing \'webimage/models\' config key');
			return null;
		}

		$modelsFile = $config->get('models');
		if (null === $modelsFile) {
			$output->writeln('Missing "models" key from \'webimage/models\' config');
			return null;
		}

		return $modelsFile;
	}

	protected function getApp(): ApplicationInterface
	{
		return $this->getContainer()->get(ApplicationInterface::class);
	}
}
