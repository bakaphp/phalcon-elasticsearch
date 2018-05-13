<?php

namespace Baka\Elasticsearch;

use \Baka\Crud\Models\Baka;
use \Elasticsearch\ClientBuilder as Client;
use \Exception;
use \Phalcon\Db\Column;
use \Phalcon\Mvc\Model;

class IndexBuilder
{
    /**
     * @var \Phalcon\Di
     */
    private static $di;

    /**
     * @var \Elasticsearch\ClientBuilder
     */
    private static $client;

    /**
     * Initialize some classes for internal use
     *
     * @return void
     */
    private static function initialize()
    {
        // Get the DI and set it to a property.
        self::$di = (new \Phalcon\Di())->getDefault();

        // Load the config through the DI.
        if (!self::$di->has('config')) {
            throw new Exception('Please add your configuration as a service (`config`).');
        }

        // Load the config through the DI.
        if (!$config = self::$di->getConfig()->get('elasticSearch')) {
            throw new Exception('Please add the elasticSearch configuration.');
        }

        // Check that there is a hosts definition for Elasticsearch.
        if (!array_key_exists('hosts', $config)) {
            throw new Exception('Please add the hosts definition for elasticSearch.');
        }

        // Instance the Elasticsearch client.
        self::$client = Client::create()->setHosts($config['hosts']->toArray())->build();
    }

    /**
     * Run checks to avoid unwanted errors.
     *
     * @param string $model
     *
     * @return string
     */
    private static function checks(string $model): string
    {
        // Call the initializer.
        self::initialize();

        // Check that there is a configuration for namespaces.
        if (!$config = self::$di->getConfig()->get('namespace')) {
            throw new Exception('Please add your namespace definitions to the configuration.');
        }

        // Check that there is a namespace definition for modules.
        if (!array_key_exists('models', $config)) {
            throw new Exception('Please add the namespace definition for your models.');
        }

        // Get the namespace.
        $namespace = $config['models'];

        // We have to do some work with the model name before we continue to avoid issues.
        $model = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $model)));

        // Check that the defined model actually exists.
        if (!class_exists($model = $namespace . '\\' . $model)) {
            throw new Exception('The specified model does not exist.');
        }

        return $model;
    }

    /**
     * Create an index for a model
     *
     * @param string $model
     * @param int $maxDepth
     *
     * @return array
     */
    public static function createIndices(string $model, int $maxDepth = 3, int $nestedLimit = 75): array
    {
        // Run checks to make sure everything is in order.
        $modelPath = self::checks($model);
        // We need to instance the model in order to access some of its properties.
        $modelInstance = new $modelPath();
        // Get the model's table structure.
        $columns = self::getFieldsTypes($model);
        // Set the model variable for use as a key.
        $model = strtolower(str_replace(['_', '-'], '', $model));

        // Define the initial parameters that will be sent to Elasticsearch.
        $params = [
            'index' => $model,
            'body' => [
                'settings' => [
                    'index.mapping.nested_fields.limit' => $nestedLimit,
                    'max_result_window' => 50000,
                    'index.query.bool.max_clause_count' => 1000000,
                    'analysis' => [
                        'analyzer' => [
                            'lowercase' => [
                                'type' => 'custom',
                                'tokenizer' => 'keyword',
                                'filter' => ['lowercase'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    $model => [
                        'properties' => [],
                    ],
                ],
            ],
        ];

        // Iterate each column to set it in the index definition.
        foreach ($columns as $column => $type) {
            if (is_array($type)) {
                // Remember we used an array to define the types for dates. This is the only case for now.
                $params['body']['mappings'][$model]['properties'][$column] = [
                    'type' => $type[0],
                    'format' => $type[1],
                ];
            } else {
                $params['body']['mappings'][$model]['properties'][$column] = ['type' => $type];

                if ($type == 'string'
                    && property_exists($modelInstance, 'elasticSearchNotAnalyzed')
                    && $modelInstance->elasticSearchNotAnalyzed
                ) {
                    $params['body']['mappings'][$model]['properties'][$column]['analyzer'] = 'lowercase';
                }
            }
        }

        // Get custom fields... fields.
        self::getCustomParams($params['body']['mappings'][$model]['properties'], $modelPath);

        // Call to get the information from related models.
        self::getRelatedParams($params['body']['mappings'][$model]['properties'], $modelPath, $modelPath, 1, $maxDepth);

        // Delete the index before creating it again
        // @TODO move this to its own function
        if (self::$client->indices()->exists(['index' => $model])) {
            self::$client->indices()->delete(['index' => $model]);
        }

        return self::$client->indices()->create($params);
    }

    /**
     * Save the object to an elastic index.
     *
     * @param Model $object
     * @param int $maxDepth
     *
     * @return array
     */
    public static function indexDocument(Model $object, int $maxDepth = 3): array
    {
        // Call the initializer.
        self::initialize();

        // Start the document we are going to insert by convertin the object to an array.
        $document = Baka::getCustomFields($object, true);

        // Use reflection to extract neccessary information from the object.
        $modelReflection = (new \ReflectionClass($object));

        self::getRelatedData($document, $object, $modelReflection->name, 1, $maxDepth);

        $params = [
            'index' => strtolower($modelReflection->getShortName()),
            'type' => strtolower($modelReflection->getShortName()),
            'id' => $object->getId(),
            'body' => $document,
        ];

        return self::$client->index($params);
    }

    /**
     * Delete a document from Elastic
     *
     * @param Model $object
     * @return array
     */
    public static function deleteDocument(Model $object): array
    {
        // Call the initializer.
        self::initialize();

        // Use reflection to extract neccessary information from the object.
        $modelReflection = (new \ReflectionClass($object));

        $params = [
            'index' => strtolower($modelReflection->getShortName()),
            'type' => strtolower($modelReflection->getShortName()),
            'id' => $object->getId(),
        ];

        return self::$client->delete($params);
    }

    /**
     * Retrieve a model's table structure so that we can define the appropiate Elasticsearch data type.
     *
     * @param string $modelPath
     *
     * @return array
     */
    private static function getFieldsTypes(string $modelPath): array
    {
        // Get the columns description.
        $columns = self::$di->getDb()->describeColumns($modelPath);
        // Define a fields array
        $fields = [];

        // Iterate the columns
        foreach ($columns as $column) {
            switch ($column->getType()) {
                case Column::TYPE_INTEGER:
                    $fields[$column->getName()] = 'integer';
                    break;
                case Column::TYPE_BIGINTEGER:
                    $fields[$column->getName()] = 'long';
                    break;
                case Column::TYPE_TEXT:
                case Column::TYPE_VARCHAR:
                case Column::TYPE_CHAR:
                    $fields[$column->getName()] = 'string';
                    break;
                case Column::TYPE_DATE:
                    // We define a format for date fields.
                    $fields[$column->getName()] = ['date', 'yyyy-MM-dd'];
                    break;
                case Column::TYPE_DATETIME:
                    // We define a format for datetime fields.
                    $fields[$column->getName()] = ['date', 'yyyy-MM-dd HH:mm:ss'];
                    break;
                case Column::TYPE_DECIMAL:
                    $fields[$column->getName()] = 'float';
                    break;
            }
        }

        return $fields;
    }

    /**
     * Get the related models structures and add them to the Elasticsearch definition.
     *
     * @param array $params
     * @param string $parentModel
     * @param string $model
     * @param int $depth
     * @param int $maxDepth
     *
     * @return void
     */
    private static function getRelatedParams(array &$params, string $parentModel, string $model, int $depth, int $maxDepth): void
    {
        $depth++;
        $relationsData = self::$di->getModelsManager()->getRelations($model);

        foreach ($relationsData as $relation) {
            $referencedModel = $relation->getReferencedModel();

            if ($referencedModel != $parentModel) {
                $referencedModel = new $referencedModel();

                $alias = strtolower($relation->getOptions()['alias']);
                $params[$alias] = ['type' => 'nested'];

                $fieldsData = self::getFieldsTypes($referencedModel->getSource());
                foreach ($fieldsData as $column => $type) {
                    // For now this is only being used for date/datetime fields
                    if (is_array($type)) {
                        $params[$alias]['properties'][$column] = [
                            'type' => $type[0],
                            'format' => $type[1],
                        ];
                    } else {
                        $params[$alias]['properties'][$column] = ['type' => $type];

                        if ($type == 'string'
                            && property_exists($referencedModel, 'elasticSearchNotAnalyzed')
                            && $referencedModel->elasticSearchNotAnalyzed
                        ) {
                            $params[$alias]['properties'][$column]['analyzer'] = 'lowercase';
                        }
                    }
                }

                self::getCustomParams($params[$alias]['properties'], $relation->getReferencedModel());

                if ($depth < $maxDepth) {
                    self::getRelatedParams(
                        $params[$alias]['properties'],
                        $parentModel,
                        $relation->getReferencedModel(),
                        $depth,
                        $maxDepth
                    );
                }
            }
        }
    }

    /**
     * Get the models custom fields structures and add them to the Elasticsearch definition.
     *
     * @param array $params
     * @param string $modelPath
     *
     * @return void
     */
    private static function getCustomParams(array &$params, string $modelPath) : void
    {
        $modelPath = explode('\\', $modelPath);
        $modelName = end($modelPath);
        $customFields = \Baka\Crud\Models\CustomFields::getFields($modelName);

        if (!is_null($customFields)) {
            $params['custom_fields'] = ['type' => 'nested'];

            foreach ($customFields as $field) {
                $type = [
                    'type' => 'string',
                    'analyzer' => 'lowercase',
                ];
                if ($field->type == 'date') {
                    $type = [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd',
                        'ignore_malformed' => true,
                    ];
                }

                $params['custom_fields']['properties'][$field->name] = $type;
            }
        }
    }

    /**
     * Get the related models data and add them to the Elasticsearch index.
     *
     * @param array $document
     * @param Model $data
     * @param string $parentModel
     * @param int $depth
     * @param int $maxDepth
     *
     * @return void
     */
    private static function getRelatedData(array &$document, Model $data, string $parentModel, int $depth, int $maxDepth): void
    {
        $depth++;
        $modelPath = (new \ReflectionClass($data))->name;
        $model = new $modelPath;

        $hasOne = self::$di->getModelsManager()->getHasOne($model);
        $belongsTo = self::$di->getModelsManager()->getBelongsTo($model);
        $hasMany = self::$di->getModelsManager()->getHasMany($model);

        $hasAll = array_merge($hasOne, $belongsTo);

        foreach ($hasAll as $has) {
            $referencedModel = $has->getReferencedModel();

            if ($referencedModel != $parentModel) {
                $alias = $has->getOptions()['alias'];
                $aliasKey = strtolower($alias);

                if ($data->$alias) {
                    //if alias exist over write it and get the none deleted
                    $alias = 'get' . $has->getOptions()['alias'];
                    $aliasRecords = $data->$alias('is_deleted = 0');

                    if ($aliasRecords) {
                        $document[$aliasKey] = Baka::getCustomFields($aliasRecords, true);

                        if ($depth < $maxDepth) {
                            self::getRelatedData($document[$aliasKey], $aliasRecords, $parentModel, $depth, $maxDepth);
                        }
                    }
                }
            }
        }

        foreach ($hasMany as $has) {
            $referencedModel = $has->getReferencedModel();

            if ($referencedModel != $parentModel) {
                $alias = $has->getOptions()['alias'];
                $aliasKey = strtolower($alias);

                if ($data->$alias->count()) {
                    //if alias exist over write it and get the none deleted
                    $alias = 'get' . $has->getOptions()['alias'];
                    $aliasRecords = $data->$alias('is_deleted = 0');

                    if (count($aliasRecords) > 0) {
                        foreach ($aliasRecords as $k => $relation) {
                            $document[$aliasKey][$k] = Baka::getCustomFields($relation, true);

                            if ($depth < $maxDepth) {
                                self::getRelatedData($document[$aliasKey][$k], $relation, $parentModel, $depth, $maxDepth);
                            }
                        }
                    }
                }
            }
        }
    }
}
