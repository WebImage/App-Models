<?php

use WebImage\Models\Commands\GenerateModelClassesCommand;
use WebImage\Models\Commands\ImportModelsCommand;
use WebImage\Models\Commands\ModelsSyncCommand;
use WebImage\Models\Providers\ModelDefinitionServiceProvider;
use WebImage\Models\Services\CommandGeneration\GenerateModelClassCommandProvider;
use WebImage\Models\TypeFields\Type;
use WebImage\Models\Properties\ValueMapper\BooleanMapper;
use WebImage\Models\Properties\ValueMapper\DateTimeMapper;
use WebImage\Models\Properties\ValueMapper\DoubleMapper;
use WebImage\Models\Properties\ValueMapper\IntegerMapper;
use WebImage\Models\View\EntityListHelper;

return [
	'console' => [
		'commands' => [
			'models:import' => ImportModelsCommand::class,
			'models:classes' => GenerateModelClassesCommand::class,
            'models:sync' => ModelsSyncCommand::class
		]
	],
	'serviceManager' => [
		'providers' => [
			GenerateModelClassCommandProvider::class,
            ModelDefinitionServiceProvider::class
		]
	],
	'webimage/models' => [
		'dataValueMappers' => [
//			'node-ref' => NodeRefMapper::class,
			'boolean' => BooleanMapper::class,
			'datetime' => DateTimeMapper::class,
			'integer' => IntegerMapper::class,
			'double' => DoubleMapper::class,
		],
		'propertyTypes' => [
			'WebImage.DataTypes.String' => ['friendly' => 'Single Line'/* , 'formElement' => 'text' */, 'field' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
			'WebImage.DataTypes.Text' => ['friendly' => 'Multi Line'/* , 'formElement' => 'textarea' */, 'field' => Type::TEXT],
			'WebImage.DataTypes.Integer' => ['friendly' => 'Integer'/* , 'formElement' => 'number' */, 'mapper' => 'integer', 'field' => Type::INTEGER],
			'WebImage.DataTypes.Decimal' => ['friendly' => 'Integer'/* , 'formElement' => 'number' */, 'mapper' => 'double', 'field' => Type::DECIMAL],
			'WebImage.DataTypes.Date' => ['friendly' => 'Date'/* , 'formElement' => 'date' */, 'field' => Type::DATE],
			'WebImage.DataTypes.DateTime' => ['friendly' => 'Date/Time'/* , 'formElement' => 'datetime' */, 'mapper' => 'datetime', 'view' => 'models/propertytypes/datetime', 'field' => Type::DATETIME],
			'WebImage.DataTypes.Boolean' => ['friendly' => 'True/False'/* , 'formElement' => 'toggle' */, 'field' => Type::BOOLEAN, 'mapper' => 'boolean'],
			'WebImage.DataTypes.Name' => ['friendly' => 'Name', 'fields' => [
				['key' => 'first', 'name' => 'First', 'type' => Type::STRING],
				['key' => 'last', 'name' => 'Last', 'type' => Type::STRING]
			]],
////			'WebImage.DataTypes.User' => ['name' => 'User'/* , 'formElement' => 'text' */, 'mapper' => 'userRef', 'field' => ['type' => Type::INTEGER]],
////			'WebImage.DataTypes.File' => ['name' => 'File'/* , 'formElement' => 'upload' */, 'mapper' => 'fileRef', 'field' => ['type' => Type::INTEGER]],
////			['type' => 'WebImage.DataTypes.EmbeddedMedia', 'name' => 'Embedded Media'/* , 'formElement' => 'text' */, 'mapper' => 'embeddedMedia', 'fields' => [
////				['key' => 'Embed', 'name' => 'embed', 'type' => Type::TEXT],
////				['key' => 'Value', 'name' => 'value', 'type' => Type::STRING, 'options' => ['length' => '255']],
////				['key' => 'Provider', 'name' => 'provider', 'type' => Type::STRING, 'options' => ['length' => '255']],
////				['key' => 'Data', 'name' => 'data', 'type' => Type::TEXT]
////			]],
////			['type' => 'WebImage.DataTypes.Link', 'name' => 'Link'/* , 'formElement' => 'link' */, 'mapper' => 'link', 'fields' => [
////				['key' => 'url', 'name' => 'url', 'type' => Type::STRING, 'options' => ['length' => '255']],
////				['key' => 'title', 'name' => 'title', 'type' => Type::STRING, 'options' => ['length' => '255']]
////			]],
////			['type' => 'WebImage.DataTypes.Address', 'name' => 'Address'/* , 'formElement' => 'address' */, 'mapper' => 'address', 'fields' => [
////				['key' => 'Street 1', 'name' => 'street1', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
////				['key' => 'Street 2', 'name' => 'street2', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
////				['key' => 'City', 'name' => 'city', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
////				['key' => 'State', 'name' => 'state', 'type' => Type::STRING, 'options' => ['length' => 3, 'notnull' => false]],
////				['key' => 'Country', 'name' => 'country', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
////				['key' => 'Zip', 'name' => 'zip', 'type' => Type::STRING, 'options' => ['length' => 10, 'notnull' => false]],
////			]],
////			'WebImage.DataTypes.TypeRef' => ['name' => 'Type References', 'mapper' => 'typeRef', 'field' => ['type' => Type::STRING ]],
////			['type' => 'WebImage.DataTypes.NodeRef', 'name' => 'Reference'/* , 'formElement' => 'text' */, 'mapper' => 'node-ref', 'fields' => [
////				['key' => 'uuid', 'name' => 'UUID', 'type' => Type::STRING, 'options' => ['length' => 255]],
////				['key' => 'version', 'name' => 'Version', 'type' => Type::INTEGER]
////			]],
////		'WebImage.DataTypes.ChildAssocRef' => ['name' => 'Child Association'/* , 'formElement' => 'text' */, 'field' => ['type' => Type::INTEGER]], //CWI_REPO_SERVICE_ChildAssociationRef'],
////		'WebImage.DataTypes.AssocRef' => ['name' => 'Association Ref'/* , 'formElement' => 'text' */, 'phpClassName' => 'CWI_REPO_SERVICE_AssociationRef']
			'virtual' => null // Do not map to anything so that a property does not get created
		],
		'propertyTypeAliases' => [
			'boolean' => 'WebImage.DataTypes.Boolean',
			'date' => 'WebImage.DataTypes.Date',
			'dateTime' => 'WebImage.DataTypes.DateTime',
			'decimal' => 'WebImage.DataTypes.Decimal',
			'integer' => 'WebImage.DataTypes.Integer',
			'string' => 'WebImage.DataTypes.String',
			'text' => 'WebImage.DataTypes.Text',
			'name' => 'WebImage.DataTypes.Name'
		]
	],
	'views' => ['helpers' => [
		'entityList' => EntityListHelper::class,
		'entity' => EntityDetailHelp::class,
	]]
];
