<?php

namespace Baka\Elasticsearch;

use Exception;
use Phalcon\Mvc\Model;
use ReflectionClass;

class IndexBuilderStructure extends IndexBuilder
{
    protected static $indexName = null;

    /**
     * Run checks to avoid unwanted errors.
     *
     * @param string $model
     *
     * @return string
     */
    protected static function checks(string $model) : string
    {
        // Call the initializer.
        self::initialize();

        // Check that the defined model actually exists.
        if (!class_exists($model)) {
            throw new Exception('The specified model does not exist.');
        }

        return $model;
    }

    /**
     * Save the object to an elastic index.
     *
     * @param Model $object
     * @param int $maxDepth
     *
     * @return array
     */
    public static function indexDocument(Model $object, int $maxDepth = 3) : array
    {
        // Call the initializer.
        self::initialize();

        // Use reflection to extract neccessary information from the object.
        $modelReflection = (new \ReflectionClass($object));
        $document = $object->document();

        $indexName = static::$indexName ?: strtolower($modelReflection->getShortName());

        $params = [
            'index' => $indexName,
            'type' => $indexName,
            'id' => $object->getId(),
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
    public static function deleteDocument(Model $object) : array
    {
        // Call the initializer.
        self::initialize();

        // Use reflection to extract neccessary information from the object.
        $modelReflection = (new \ReflectionClass($object));
        $object->document();

        $indexName = static::$indexName ?: strtolower($modelReflection->getShortName());

        $params = [
            'index' => $indexName,
            'type' => $indexName,
            'id' => $object->getId(),
        ];

        return self::$client->delete($params);
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
     * Check if the index exist.
     *
     * @param string $model
     * @return void
     */
    public static function existIndices(string $model): bool
    {
        // Run checks to make sure everything is in order.
        $modelPath = self::checks($model);

        // We need to instance the model in order to access some of its properties.
        $modelInstance = new $modelPath();
        $model = self::$indexName ?: strtolower(str_replace(['_', '-'], '', (new ReflectionClass($modelInstance))->getShortName()));

        return self::$client->indices()->exists(['index' => $model]);
    }

    /**
     * Create an index for a model.
     *
     * @param string $model
     * @param int $maxDepth
     *
     * @return array
     */
    public static function createIndices(string $model, int $maxDepth = 3, int $nestedLimit = 75) : array
    {
        // Run checks to make sure everything is in order.
        $modelPath = self::checks($model);

        // We need to instance the model in order to access some of its properties.
        $modelInstance = new $modelPath();

        // Get the model's table structure.
        $columns = $modelInstance->structure();

        // Set the model variable for use as a key.
        $model = self::$indexName ?: strtolower(str_replace(['_', '-'], '', (new ReflectionClass($modelInstance))->getShortName()));

        // Define the initial parameters that will be sent to Elasticsearch.
        $params = [
            'index' => $model,
            'body' => [
                'settings' => self::getIndicesSettings($nestedLimit),
                'mappings' => [
                    $model => [
                        'properties' => [],
                    ],
                ],
            ],
        ];

        // Iterate each column to set it in the index definition.
        foreach ($columns as $column => $type) {
            if (is_array($type) && isset($type[0])) {
                // Remember we used an array to define the types for dates. This is the only case for now.
                $params['body']['mappings'][$model]['properties'][$column] = [
                    'type' => $type[0],
                    'format' => $type[1],
                ];
            } elseif (!is_array($type)) {
                $params['body']['mappings'][$model]['properties'][$column] = ['type' => $type];

                if ($type == 'string') {
                    $params['body']['mappings'][$model]['properties'][$column]['analyzer'] = 'lowercase';
                }
            } else {
                //nested
                self::mapNestedProperties($params['body']['mappings'][$model]['properties'], $column, $type);
            }
        }

        // Delete the index before creating it again
        // @TODO move this to its own function
        if (self::$client->indices()->exists(['index' => $model])) {
            self::$client->indices()->delete(['index' => $model]);
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
                //fix issues when neste arrays  contains another array with no fields
                if (!array_key_exists('properties', $params[$column])) {
                    $params[$column]['properties'] = [];
                }
                self::mapNestedProperties($params[$column]['properties'], $innerColumn, $type);
            }
        }
    }
}
