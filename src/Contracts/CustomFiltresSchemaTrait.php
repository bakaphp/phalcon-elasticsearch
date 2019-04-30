<?php

namespace Baka\Elasticsearch\Contracts;

use Phalcon\Http\Response;
use stdClass;

/**
 * Search controller.
 */
trait CustomFiltresSchemaTrait
{
    /**
     * Given the indice get the Schema configuration for the filter.
     *
     * @param string $indice
     * @return array
     */
    public function getSchema(string $index): array
    {
        $mapping = $this->elastic->indices()->getMapping([
            'index' => $index,
        ]);

        $mapping = array_shift($mapping);

        //if we dont find mapping return empty
        if (!isset($mapping['mappings'])) {
            return [];
        }

        $mapping = array_shift($mapping);

        //we only need the infro fromt he properto onward
        return $this->mappingToArray(array_shift($mapping)['properties']);
    }

    /**
     * Generate the array map fromt he elastic search mapping.
     *
     * @param array $mappings
     * @param string $parent
     * @return array
     */
    protected function mappingToArray(array $mappings, string $parent = null): array
    {
        $result = [];

        foreach ($mappings as $key => $mapping) {
            if (isset($mapping['type']) && $mapping['type'] != 'nested') {
                $result[] = $parent . $key;
            } elseif (isset($mapping['type']) && $mapping['type'] == 'nested' && is_array($mapping)) {

                //setup key
                $parent .= $key . '.';

                //look for more records
                $result[] = $this->mappingToArray($mapping['properties'], $parent);

                //so we finisht with a child , we need to change the parent to one back
                $parentExploded = explode('.', $parent);
                $parent = count($parentExploded) > 2 ? $parentExploded[0] . '.' : null;
            }
        }

        return $result;
    }
}
