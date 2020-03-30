<?php

namespace Baka\Elasticsearch;

use Exception;
use Phalcon\Mvc\Model;
use ReflectionClass;
use Phalcon\Mvc\ModelInterface;

class IndexBuilderStructure extends IndexBuilder
{
    protected static $indexName = null;

    /**
     * Confirm the current model has ElasticIndex Trait
     *
     * @param ModelInterface $model
     * @return void
     */
    protected static function checks(ModelInterface $model): void
    {
        if (!method_exists($model, 'document')) {
            throw new Exception('Add the ElasticIndexTrait to your model in order to use this function');
        }
    }

    /**
     * Given the need to use this same structure and have diff index with diff name, overwrite the name.
     *
     * @param string $indexName
     * @return void
     */
    public static function setIndexName(string $indexName): void
    {
        static::$indexName = strtolower($indexName);
    }

    /**
     * Overwrite get instance name to use manual name
     *
     * @param ModelInterface $model
     * @return string
     */
    public static function getIndexName(ModelInterface $model): string
    {
        return static::$indexName ?: parent::getIndexName($model);
    }

    /**
     * Save the object to an elastic index.
     *
     * @param Model $object
     * @param int $maxDepth
     *
     * @return array
     */
    public static function indexDocument(ModelInterface $model, int $maxDepth = 3) : array
    {
        // Call the initializer.
        self::initialize();
        self::checks($model);

        $document = $model->document();

        $indexName = self::getIndexName($model);

        $params = [
            'index' => $indexName,
            'id' => $model->getId(),
            'body' => $document,
        ];

        return self::$client->index($params);
    }

    /**
     * Delete a document from Elastic.
     *
     * @param Model $object
     * @return array
     */
    public static function deleteDocument(ModelInterface $model) : array
    {
        // Call the initializer.
        self::initialize();
        self::checks($model);

        $indexName = self::getIndexName($model);

        $params = [
            'index' => $indexName,
            'id' => $model->getId(),
        ];

        return self::$client->delete($params);
    }

    /**
     * Check if the index exist.
     *
     * @param string $model
     * @return void
     */
    public static function existIndices(ModelInterface $model): bool
    {
        // Run checks to make sure everything is in order.
        self::checks($model);

        return self::$client->indices()->exists(['index' => self::getIndexName($mode)]);
    }

    /**
     * Create an index for a model.
     *
     * @param string $model
     * @param int $maxDepth
     *
     * @return array
     */
    public static function createIndices(ModelInterface $model, int $maxDepth = 3, int $nestedLimit = 75) : array
    {
        // Run checks to make sure everything is in order.
        self::checks($model);

        // Get the model's table structure.
        $columns = $model->structure();

        // Set the model variable for use as a key.
        $index = self::getIndexName($model);

        // Define the initial parameters that will be sent to Elasticsearch.
        $params = [
            'index' => $index,
            'body' => [
                'settings' => self::getIndicesSettings($nestedLimit),
                'mappings' => [
                ],
            ],
        ];

        // Iterate each column to set it in the index definition.
        foreach ($columns as $column => $type) {
            if (is_array($type) && isset($type[0])) {
                // Remember we used an array to define the types for dates. This is the only case for now.
                $params['body']['mappings']['properties'][$column] = [
                    'type' => $type[0],
                    'format' => $type[1],
                ];
            } elseif (!is_array($type)) {
                $params['body']['mappings']['properties'][$column] = ['type' => $type];

                if ($type == 'string') {
                    $params['body']['mappings']['properties'][$column]['analyzer'] = 'lowercase';
                }
            } else {
                //nested
                self::mapNestedProperties($params['body']['mappings']['properties'], $column, $type);
            }
        }

        // Delete the index before creating it again
        // @TODO move this to its own function
        if (self::$client->indices()->exists(['index' => $index])) {
            self::$client->indices()->delete(['index' => $index]);
        }

        return self::$client->indices()->create($params);
    }

    /**
     * Map the neste properties of a index by using recursive calls.
     *
     * @todo we are reusing this code on top so we must find a better way to handle it @kaioken
     *
     * @param array $params
     * @param string $column
     * @param array $columns
     * @return void
     */
    protected static function mapNestedProperties(array &$params, string $column, array $columns): void
    {
        $params[$column] = ['type' => 'nested'];

        foreach ($columns as $innerColumn => $type) {
            // For now this is only being used for date/datetime fields
            if (is_array($type) && isset($type[0])) {
                $params[$column]['properties'][$innerColumn] = [
                    'type' => $type[0],
                    'format' => $type[1],
                ];
            } elseif (!is_array($type)) {
                $params[$column]['properties'][$innerColumn] = ['type' => $type];

                if ($type == 'string') {
                    $params[$column]['properties'][$innerColumn]['analyzer'] = 'lowercase';
                }
            } else {
                //fix issues when nested arrays  contains another array with no fields
                if (!array_key_exists('properties', $params[$column])) {
                    $params[$column]['properties'] = [];
                }
                self::mapNestedProperties($params[$column]['properties'], $innerColumn, $type);
            }
        }
    }
}
