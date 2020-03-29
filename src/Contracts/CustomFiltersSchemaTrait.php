<?php

namespace Baka\Elasticsearch\Contracts;

/**
 * Search controller.
 */
trait CustomFiltersSchemaTrait
{
    /**
     * Given the index get the Schema configuration for the filter.
     *
     * @param string $index
     * @return array
     */
    public function getSchema(string $index): array
    {
        $mapping = $this->elastic->indices()->getMapping([
            'index' => $index,
        ]);

        $mapping = array_shift($mapping);

        //if we don't find mapping return empty
        if (!isset($mapping['mappings'])) {
            return [];
        }

        $mapping = array_shift($mapping);

        //we only need the info from the property onward
        //we want the result to be in a linear array so we pass it by reference
        $result = [];
        $results = $this->mappingToArray(array_shift($mapping)['properties'], null, $result);
        rsort($results); //revert order?
        return $results;
    }

    /**
     * Generate the array map from the elastic search mapping.
     *
     * @param array $mappings
     * @param string $parent
     * @param array $result
     * @return array
     */
    protected function mappingToArray(array $mappings, string $parent = null, array &$result): array
    {
        foreach ($mappings as $key => $mapping) {
            if (isset($mapping['type']) && $mapping['type'] != 'nested') {
                $result[] = $parent . $key;
            } elseif (isset($mapping['type']) && $mapping['type'] == 'nested' && is_array($mapping)) {
                //setup key
                $parent .= $key . '.';

                //look for more records
                $this->mappingToArray($mapping['properties'], $parent, $result);

                //so we finish with a child , we need to change the parent to one back
                $parentExploded = explode('.', $parent);
                $parent = count($parentExploded) > 2 ? $parentExploded[0] . '.' : null;
            }
        }

        return $result;
    }
}
