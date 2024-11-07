<?php

namespace WebImage\Models\Services\Db;

use WebImage\Core\ArrayHelper;
use WebImage\Models\Defs\DataTypeDefinition;
use WebImage\Models\Defs\ModelDefinition;
use WebImage\Models\Defs\PropertyDefinition;
use WebImage\Models\Entities\Entity;
use WebImage\Models\Services\RepositoryInterface;

function capture(callable $callback): string
{
	ob_start();
	$callback();
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

class ModelTableDebugger
{
	public static function debugModelTree(RepositoryInterface $repository, string $modelName)
	{
		self::dumpStyles();
		self::showModelTableTree($repository, $modelName);
	}

	private static function showModelTableTree(RepositoryInterface $repo, string $model, array $parentModels = [])
	{
		$modelDef = $repo->getModelService()->getModel($model)->getDef();
		?>
        <div class="debug-node">
			<?php if (in_array($model, $parentModels)): ?>
                Recursive: <?= $model ?>
			<?php else: ?>
				<?= $model ?>
				<?php self::showModelProperties($repo, $modelDef, array_merge($parentModels, [$model])) ?>
			<?php endif ?>
        </div>
		<?php
	}

	private static function showModelProperties(RepositoryInterface $repo, ModelDefinition $modelDef, array $parentModels)
	{
		?>
        <div class="debug-node-properties">
			<?php foreach ($modelDef->getProperties() as $propDef): ?>
				<?php self::showModelProperty($repo, $propDef, $parentModels); ?>
			<?php endforeach ?>
        </div>
		<?php
	}

	private static function showModelProperty(RepositoryInterface $repo, PropertyDefinition $propDef, array $parentModels)
	{
		$modelDef   = $repo->getModelService()->getModel($propDef->getModel())->getDef();
		$modelTable = TableNameHelper::getTableNameFromDef($modelDef);
		$dataType   = $repo->getDataTypeService()->getDefinition($propDef->getDataType());
		?>
        <div class="debug-node-property-node">
            <div class="debug-node-property
                <?php if ($propDef->isMultiValued()): ?>debug-node-property-multivalued<?php endif ?>
                <?php if ($propDef->isVirtual()): ?>debug-node-property-virtual<?php endif ?>
            ">
                <div class="debug-node-property-name"><?= $propDef->getName() ?><?php if ($propDef->isMultiValued()): ?>[]<?php endif ?></div>
                <div class="debug-node-property-type"><?= $propDef->getDataType() ?></div>
            </div>
			<?php if ($propDef->isVirtual()): ?>
				<?php if ($propDef->hasReference()): ?>
					<?php self::showModelTableTree($repo, $propDef->getReference()->getTargetModel(), $parentModels) ?>
				<?php else: ?>
                    Missing Reference
				<?php endif ?>
			<?php else: ?>
				<?php if (!$dataType->isSimpleStorage()): ?>
					<?php self::showComplexDataType($repo, $dataType) ?>
				<?php endif ?>
				<?php if ($propDef->isMultiValued()): ?>
                    <div>Values Table</div>
				<?php endif ?>
			<?php endif ?>
        </div>
		<?php
	}

	private static function showModelTable(RepositoryInterface $repo, string $model, array $parentModels = [])
	{
		echo '<div class="model-table-tree">';
		echo $model . '<br/>' . PHP_EOL;
		$modelDef   = $repo->getModelService()->getModel($model)->getDef();
		$modelTable = TableNameHelper::getTableNameFromDef($modelDef);
		?>
        <table class="debug-table">
            <thead>
            <tr>
                <th>Property</th>
                <th>Type</th>
                <th>Structure</th>
                <th>Virtual</th>
                <th>Multi-Valued</th>
                <th>Integration</th>
                <th>Associated Table</th>
                <th>Select</th>
                <th>Join Columns</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($modelDef->getProperties() as $propDef):
				$dataType = $repo->getDataTypeService()->getDefinition($propDef->getDataType());
				?>
                <tr <?php if ($propDef->isPrimaryKey()): ?> class="debug-primary-key"<?php endif ?>>
                    <td><?= $propDef->getName() ?></td>
                    <td><?= $propDef->getDataType() ?></td>
                    <td style="text-align: center">
						<?php if ($propDef->isVirtual()): ?>
                            (virtual)
						<?php else: ?>
							<?= $dataType->isSimpleStorage() ? 'Simple' : 'Compound' ?>
						<?php endif ?>
                    </td>
                    <td style="text-align: center"><?= $propDef->isVirtual() ? 'x' : '' ?></td>
                    <td style="text-align: center"><?= $propDef->isMultiValued() ? 'x' : '' ?></td>
					<?php if ($propDef->isVirtual()):
						?>
						<?php if ($propDef->isMultiValued()):
						$refModelTable = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
						$targetModelDef = $repo->getModelService()->getModel($propDef->getReference()->getTargetModel())->getDef();
						$targetModelTable = TableNameHelper::getTableNameFromDef($targetModelDef);
//						$refModelTable = TableNameHelper::getTableNameFromDef($propModelDef);
						?>
                        <td>Lazy Collection</td>
                        <td><!-- associated table-->
							<?= $refModelTable ?><br/>
							<?= $targetModelTable ?>
                        </td>
                        <td>
                            Load Separately
							<?php /*
                        Node Stub:<br/>
							<?php foreach (self::getModelPrimaryKeys($modelDef) as $primaryKey): ?>
                                <div class="debug-select-column">
                                    <div class="debug-property">
                                        X
                                    </div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnName($refModelTable, TableNameHelper::getColumnKey($modelTable, $propDef->getName(), $primaryKey->getName())) ?>
                                    </div>
                                    <div class="debug-equate">as</div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnKey(TableNameHelper::getColumnKey($modelTable, $propDef->getName(), $primaryKey->getName())) ?>
                                    </div>
                                </div>
							<?php endforeach ?>
							<?php foreach (self::getModelPrimaryKeys($targetModelDef) as $primaryKey): ?>
                                <div class="debug-select-column">
                                    <div class="debug-property">
                                        Y
                                    </div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnName($refModelTable, TableNameHelper::getColumnKey($targetModelTable, $primaryKey->getName())) ?>
                                    </div>
                                    <div class="debug-equate">as</div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnNameAlias($refModelTable, TableNameHelper::getColumnKey($targetModelTable, $primaryKey->getName())) ?>
                                    </div>
                                </div>
							<?php endforeach ?>
                            <hr/>
                            Target:<br/>
							<?php foreach ($targetModelDef->getProperties() as $refPropDef):
                                $dataType = $repo->getDataTypeService()->getDefinition($refPropDef->getDataType());
								?>
                            <?= $propDef->getName() . ' - ' . $refPropDef->getName() . ' -  ' .$dataType->getName() ?><br/>
                            <?php
                                foreach($dataType->getModelFields() as $modelField): ?>
                                <div class="debug-select-column">
                                    <div class="debug-property">
                                        <?= $propDef->getName() ?>[x][<?= $refPropDef->getName() ?>]<?= $modelField->getKey() ? '[' . $modelField->getKey() . ']' : '' ?>
                                    </div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnName($targetModelTable, TableNameHelper::getColumnKey($refPropDef->getName())) ?>
                                    </div>
                                    <div class="debug-equate">as</div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnNameAlias($targetModelTable, TableNameHelper::getColumnKey($refPropDef->getName())) ?>
                                    </div>
                                </div>
							<?php endforeach ?>
							<?php endforeach ?>
                            <!-- LEFT JOIN -->
                               */ ?>
                        </td> <!-- select -->
                        <td>
							<?php /* JOIN<br/>
							<?php foreach (self::getModelPrimaryKeys($targetModelDef) as $primaryKey):
								$dataType = $repo->getDataTypeService()->getDefinition($primaryKey->getDataType());
								foreach ($dataType->getModelFields() as $modelField): ?>
                                    <div class="debug-select-column">
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnName($targetModelTable, TableNameHelper::getColumnKey($primaryKey->getName(), $modelField->getKey())) ?>
                                        </div>
                                        <div class="debug-equate">=</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnName($refModelTable, TableNameHelper::getColumnKey($targetModelTable, $primaryKey->getName())) ?>
                                        </div>
                                    </div>
								<?php endforeach ?>
							<?php endforeach ?>
                            <hr/>
                            WHERE<br/>
							<?php foreach (self::getModelPrimaryKeys($modelDef) as $primaryKey): ?>
                                <div class="debug-select-column">
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnName($refModelTable, TableNameHelper::getColumnKey($modelTable, $propDef->getName(), $primaryKey->getName())) ?>
                                    </div>
                                    <div class="debug-equate">=</div>
                                    <div class="debug-tag">
                                        $entity[<?= $primaryKey->getName() ?>]
                                    </div>
                                </div>
							<?php endforeach ?>
                            <hr/>
 */ ?> Load separately
                        </td> <!-- join -->
					<?php else: // NOT Virtual
						$targetModelDef = $repo->getModelService()->getModel($propDef->getReference()->getTargetModel())->getDef();
						$refModelTable = TableNameHelper::getTableNameFromDef($targetModelDef);
						?>
                        <td>Lazy Inline</td>
                        <td><?= $refModelTable ?></td> <!-- associated table-->
                        <td>
							<?php foreach (self::getModelPrimaryKeys($targetModelDef) as $primaryKey):
								$primaryKeyDataType = $repo->getDataTypeService()->getDefinition($primaryKey->getDataType());
								foreach ($primaryKeyDataType->getModelFields() as $modelField): ?>
                                    <div class="debug-select-column">
                                        <div class="debug-property">
											<?= $propDef->getName() . '[' . $primaryKey->getName() . '] ' . ($modelField->getKey() ? '[' . $modelField->getKey() . ']' : '') ?>
                                        </div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getTableColumnName($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $primaryKey->getName())) ?>
                                        </div>
                                        <div class="debug-equate">as</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnNameAlias($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $primaryKey->getName())) ?>
                                        </div>
                                    </div>
								<?php endforeach ?>
							<?php endforeach ?>
                            <hr/>
							<?php foreach ($targetModelDef->getProperties() as $refPropDef): ?>
                                <div class="debug-select-column">
                                    <div class="debug-property">
										<?= $propDef->getName() . '[' . $refPropDef->getName() . ']' ?>
                                    </div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getTableColumnName($refModelTable, TableNameHelper::getColumnKey($refPropDef->getName())) ?>
                                    </div>
                                    <div class="debug-equate">as</div>
                                    <div class="debug-tag">
										<?= TableNameHelper::getColumnNameAlias($refModelTable, TableNameHelper::getColumnKey($refPropDef->getName())) ?>
                                    </div>
                                </div>
							<?php endforeach ?>
                        </td> <!-- select -->
                        <td>
							<?php foreach (self::getModelPrimaryKeys($modelDef) as $primaryKey):
								$dataType = $repo->getDataTypeService()->getDefinition($primaryKey->getDataType());
								foreach ($dataType->getModelFields() as $modelField): ?>
                                    <div class="debug-select-column">
                                        <div class="debug-property">
											<?= $primaryKey->getName() ?><?php if ($modelField->getKey()): ?>[<?= $modelField->getKey() ?>]<?php endif ?>
                                        </div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getTableColumnName($refModelTable, TableNameHelper::getColumnKey($primaryKey->getName())) ?>
                                        </div>
                                        <div class="debug-equate">=</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getTableColumnName($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $primaryKey->getName())) ?>
                                        </div>
                                    </div>
								<?php endforeach ?>
							<?php endforeach ?>
                        </td> <!-- join -->
					<?php endif ?>
					<?php else: ?>
						<?php if ($propDef->isMultiValued()):
							$refModelTable = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
							?>
                            <td>Value Table</td>
                            <td><?= $refModelTable ?></td>
                            <td>
                                Load Separately
								<?php /*
								<?php foreach ($dataType->getModelFields() as $modelField): ?>
                                    <div class="debug-select-column">
                                        <div class="debug-property">
											<?= $propDef->getName() ?>[x]<?= $modelField->getKey() ? '.' . $modelField->getKey() : '' ?>
                                        </div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnName($refModelTable, TableNameHelper::getColumnKey($propDef->getName()), $modelField->getKey()) ?>
                                        </div>
                                        <div class="debug-equate">as</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey()) ?>
                                        </div>
                                    </div>
								<?php endforeach ?>
 */ ?>
                            </td> <!-- local columns -->
                            <td>
                                Load Separately <?php /*
								<?php foreach (self::getModelPrimaryKeys($modelDef) as $primaryKey):
									$dataType = $repo->getDataTypeService()->getDefinition($primaryKey->getDataType());
									?>
                                    <div class="debug-select-column">
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnName($refModelTable, $modelTable, TableNameHelper::getColumnKey($primaryKey->getName())) ?>
                                        </div>
                                        <div class="debug-equate">=</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnName($modelTable, TableNameHelper::getColumnKey($primaryKey->getName())) ?>
                                        </div>
                                    </div>
								<?php endforeach ?> */ ?>
                            </td> <!-- join columns -->
						<?php else: ?>
                            <td>Direct</td>
                            <td>none</td>
                            <td>
								<?php foreach ($dataType->getModelFields() as $modelField): ?>
                                    <div class="debug-select-column">
                                        <div class="debug-property">
											<?= $propDef->getName() ?><?= $modelField->getKey() ? '.' . $modelField->getKey() : '' ?>
                                        </div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getTableColumnName($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey() ?? '')) ?>
                                        </div>
                                        <div class="debug-equate">as</div>
                                        <div class="debug-tag">
											<?= TableNameHelper::getColumnNameAlias($model, TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey())) ?>
                                        </div>
                                    </div>

								<?php endforeach; ?>
                            </td>
                            <td>none</td>
						<?php endif ?>
					<?php endif ?>
                </tr>
			<?php endforeach;
			?>
            </tbody>
        </table>
		<?php
	}

	/**
	 * @param ModelDefinition $modelDef
	 * @return PropertyDefinition[]
	 */
	private static function getModelPrimaryKeys(ModelDefinition $modelDef): array
	{
		$primaryKeys = [];
		foreach ($modelDef->getProperties() as $propDef) {
			if ($propDef->isPrimaryKey()) {
				$primaryKeys[] = $propDef;
			}
		}

		return $primaryKeys;
	}

	private static function showComplexDataType(RepositoryInterface $repo, DataTypeDefinition $dataType)
	{
		?>
        <div class="debug-node-datatypes">
			<?php foreach ($dataType->getModelFields() as $modelField): ?>
                <div class="debug-node-datatype"><?= $modelField->getKey() ?> <?= $modelField->getType() ?></div>
			<?php endforeach ?>
        </div>
		<?php
	}

	/**
	 * |---------------|-------------------------------|-------------------|------------------|
	 * |               | Virtual                       | Simple            | Complex          |
	 * |---------------|-------------------------------|-------------------|------------------|
	 * | Multi-valued  | Model Table (n)               |  Value Table      | Value Table      | <- Post retrieval (after primary query)
	 * |               | Different table               |  Different table  | Different table  | <- Same or different table
	 * |---------------|-------------------------------|-------------------|------------------|
	 * | Single value  | Model Table (1)               | Column in         | Columns in       | <-- Retrieve primary key at query time and supply lazy entity reference
	 * |               | Different Table               | SAME table        | SAME table       | <- Same or different table
	 * |---------------|-------------------------------|-------------------|------------------|
	 *
	 * @param RepositoryInterface $repo
	 * @param ModelDefinition $modelDef
	 * @return void
	 */
	public static function getModelMapping(RepositoryInterface $repo, ModelDefinition $modelDef)
	{
		self::dumpStyles();
		$plan = new ModelTablePlan($modelDef->getName(), TableNameHelper::getTableNameFromDef($modelDef));
		?>
		<?= $modelDef->getName() ?><br/>
        <table class="debug-table">
            <thead>
            <tr>
                <th>Property</th>
                <th>Primary</th>
                <th>Multi-Valued</th>
                <th>Load</th>
                <th>Local/Virtual</th>
                <th>Columns</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ($modelDef->getProperties() as $propDef):
				if (!$propDef->isVirtual()) continue;
//				if ($propDef->isMultiValued()) continue;

				?>
                <tr>
                    <td><?= $propDef->getName() ?></td>
                    <td style="text-align: center"><?= $propDef->isPrimaryKey() ? 'x' : '' ?></td>
                    <td style="text-align: center"><?= $propDef->isMultiValued() ? 'x' : '' ?></td>
                    <td>
						<?php if ($propDef->isMultiValued() || $propDef->isVirtual()): ?>
                            Lazy
						<?php else: ?>
                            Immediate
						<?php endif ?>
                    </td>
                    <td><?= $propDef->isVirtual() ? 'Virtual' : 'Local' ?></td>
                    <td>
						<?php
						echo self::getDBColumns($repo, $propDef, $propPlan);
						?>
                    </td>
                </tr>
			<?php endforeach ?>
            </tbody>
        </table>
		<?php
		die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
	}

	private static function getDBColumns(RepositoryInterface $repo, PropertyDefinition $propDef, ModelPropertyPlan $propPlan)
	{
		return capture(function () use ($repo, $propDef, $propPlan) {
			$modelDef   = $repo->getModelService()->getModel($propDef->getModel())->getDef();
			$modelTable = TableNameHelper::getTableNameFromDef($modelDef);
			?>
            <table class="debug-table-join">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Local</th>
                    <th>Key</th>
                    <th>Table</th>
                    <th>Column</th>
                    <th>Alias</th>
                </tr>
                </thead>
                <tbody>
				<?php
				if ($propDef->isVirtual()) {
					if (!$propDef->hasReference()) die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);// 'No reference';

					$refModelDef = $repo->getModelService()->getModel($propDef->getReference()->getTargetModel())->getDef();
					// Get columns from other table
					foreach ($refModelDef->getProperties() as $refPropDef) {
						$dataType = $repo->getDataTypeService()->getDefinition($refPropDef->getDataType());
//						if (!$refPropDef->isPrimaryKey()) continue;

						?>
                        <tr <?php if ($refPropDef->isPrimaryKey()): ?> class="debug-primary-key"<?php elseif ($refPropDef->isVirtual()): ?> class="debug-virtual-key"<?php endif ?>>
							<?php
							if ($refPropDef->isVirtual()) {
								$refPropModelDef = $repo->getModelService()->getModel($refPropDef->getReference()->getTargetModel())->getDef();
//                                $propPlan->addColumn(new ModelPropertyColumn());
								?>
                                <td>Virtual</td>
                                <td>$entity[<?= $propDef->getName() ?>]<?= ($propDef->isMultiValued() ? '[x]' : '') ?>[<?= $refPropDef->getName() ?>]</td>
                                <td><?= $refPropDef->getName() ?> <em>(stub)</em></td>
                                <td><?= TableNameHelper::getTableNameFromDef($refPropModelDef) ?></td>
                                <td>virtual = <?= $refPropDef->getReference()->getTargetModel() ?></td>
                                <td><em>None</em></td>
								<?php
							} else if ($dataType->isSimpleStorage()) {
//								$table = TableNameHelper::getTableNameFromDef($modelDef, $propDef->getName());
								$table = TableNameHelper::getTableNameFromDef($refModelDef);
								?>
                                <td>Lazy</td>
                                <!--                                <td>$entity[--><?php //= $propDef->getName() ?><!--]--><?php //= ($propDef->isMultiValued() ? '[x]' : '') ?><!--[--><?php //= $refPropDef->getName() ?><!--]</td>-->
                                <td>
									<?php if ($refPropDef->isPrimaryKey()): ?>
										<?= TableNameHelper::getTableColumnName($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $refPropDef->getName())) ?>
									<?php else: ?>
                                        n/a
									<?php endif ?>
                                </td>
                                <td><?= $refPropDef->getName() ?></td>
                                <td><?= $table ?></td>
                                <td><?= TableNameHelper::getColumnKey($refPropDef->getName()) ?></td>
                                <td><?= TableNameHelper::getColumnNameAlias($table, $refPropDef->getName()) ?></td>
								<?php
							} else {
								die(__FILE__ . ':' . __LINE__ . '<br />' . PHP_EOL);
							}
							?>
                        </tr>
						<?php
					}
				} else {
					$dataType = $repo->getDataTypeService()->getDefinition($propDef->getDataType());
					if ($dataType->isSimpleStorage()) {
						?>
                        <tr>
                            <td>Simple.Single</td>
                            <td>$entity[<?= $propDef->getName() ?>]</td>
                            <td><em>Same</em></td>
                            <td><em>Same</em></td>
                            <td><?= TableNameHelper::getColumnKey($propDef->getName()) ?></td>
                            <td><?= TableNameHelper::getColumnNameAlias($modelTable, $propDef->getName()) ?></td>
                        </tr>
						<?php
					} else {
						$modelFields = $dataType->getModelFields();

						foreach ($modelFields as $modelField) {
							?>
                            <tr>
                                <td>Simple.Compound</td>
                                <td>$entity[<?= $propDef->getName() ?>][<?= $modelField->getKey() ?>]</td>
                                <td><?= $modelField->getKey() ?></td>
                                <td><em>Same</em></td>
                                <td><?= TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey()) ?></td>
                                <td><?= TableNameHelper::getColumnNameAlias($modelTable, TableNameHelper::getColumnKey($propDef->getName(), $modelField->getKey())) ?></td>
                            </tr>
							<?php
						}
					}
				}
				?>
                </tbody>
            </table>
			<?php
		});
	}

	private static function dumpStyles()
	{
		?>
        <style type="text/css">
            body {
                font-family: sans-serif;
            }

            .debug-node, .debug-node-property {
                background-color: #fff;
                border: 1px solid #000;
                border-radius: 10px;
                display: flex;
                align-items: center;
            }

            .debug-node {
                padding: 10px;
                gap: 2px;
            }

            .debug-node-properties {
                /*background-color: #ccc;*/
                border: 1px solid #999;
                border-radius: 10px;
                display: flex;
                flex-direction: column;
                gap: 2px;
                padding: 5px;
            }

            .debug-node-properties.hidden .debug-node-property-node {
                display: none;
            }

            .debug-node-properties .debug-node-properties-toggle {
                /*display: block;*/
            }

            .debug-node-properties.hidden .debug-node-properties-toggle {
                /*display: none;*/
            }

            .debug-node-property-node {
                display: flex;
                border-bottom: 1px solid #000;
                padding: 2px;
                gap: 5px;
            }

            .debug-node-property {
                gap: 10px;
                padding: 2px 10px;
            }

            .debug-node-property-multivalued {
                border: 1px dashed #03c;
            }

            .debug-node-property-virtual {
                background-color: #cfc;
                /*border-color: #0f0;*/
            }

            .debug-node-property-name {
                color: #30a;
                font-size: 0.9em;
            }

            .debug-node-property-type {
                color: #09c;
                font-size: 0.9em;
                font-style: italic;
                margin-left: auto;
            }

            .debug-table, .debug-table-join {
                border-collapse: collapse;
            }

            .debug-table .debug-primary-key {
                background-color: #0d0;
            }

            .debug-table th, .debug-table td {
                border: 1px solid #000;
                padding: 5px;
            }

            .debug-table-join thead tr {
                background-color: #ffa500;
            }

            .debug-table-join tbody tr {
                background-color: #aaefff;
            }

            .debug-table-join tbody tr.debug-primary-key {
                background-color: #0d0;
            }

            .debug-table-join tbody tr.debug-virtual-key {
                background-color: #ff0;
            }

            .model-table-tree {
                border: 1px solid #000;
                padding: 10px;
                margin: 10px;
            }

            .debug-select-column {
                background-color: #ddd;
                border: 1px solid #000;
                border-radius: 5px;
                /*display: flex;*/
                font-size: 15px;
                margin: 3px;
                width: auto;
                white-space: nowrap;
            }

            .debug-tag {
                border-left: none;
            }

            .debug-tag, .debug-select-column .debug-equate, .debug-property {
                font-size: 0.8em;
                padding: 5px;
                display: inline-block;
            }

            .debug-property {
                background-color: #090;
                color: #fff;
            }

            .debug-select-column .debug-equate {
                background-color: #000;
                color: #fff;
                display: inline-block;
                text-transform: uppercase;
            }
        </style>
		<?php
	}

	private static function debugType($value)
	{
		if (is_array($value)) {
			if (ArrayHelper::isAssociative($value)) {
				return 'array(' . implode(', ', array_keys($value)) . ')';
			} else {
				return 'array(' . count($value) . ')';
			}
		} else if (is_object($value)) {
			$objVars = get_object_vars($value);
			if (count($objVars) > 0) {
				return get_class($value) . '(' . implode(', ', array_keys($objVars)) . ')';
			} else {
				return substr(get_class($value), strrpos(get_class($value), '\\') + 1);
			}
//            return get_class($value);
		} else if (is_string($value)) {
			return '"' . $value . '"';// (string)';
		} else if (is_int($value)) {
			return 'int';
		} else if (is_float($value)) {
			return 'float';
		} else if (is_bool($value)) {
			return 'bool';
		} else if (is_null($value)) {
			return 'null';
		} else {
			return 'unknown';
		}
	}
}