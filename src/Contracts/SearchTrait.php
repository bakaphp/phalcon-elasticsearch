<?php

namespace Baka\Elasticsearch\Contracts;

use Phalcon\Http\Response;
use stdClass;

/**
 * Search controller
 */
trait SearchTrait
{
    /**
     * get the search by it id
     *
     * @param string $id
     *
     * @return Response
     */
    public function search(string $model, bool $withLimit = true, $paramLimit = null): Response
    {
        $query = $this->request->getQuery('q');
        $nestedQuery = $this->request->getQuery('nq');
        $fields = $this->request->getQuery('fields', null, '*');
        $page = $this->request->getQuery('page', null, 1);

        $limit = 3000;

        if ($this->request->hasQuery('limit')) {
            $limit = $this->request->getQuery('limit', null, 50);
        } elseif ($this->request->hasQuery('per_page')) {
            $limit = $this->request->getQuery('per_page', null, 50);
        }
        if ($paramLimit) {
            $limit = $paramLimit;
        }

        $sort = $this->request->getQuery('sort', null, 'id ASC');
        if (empty($sort)) {
            $sort = 'id ASC';
        }

        $sort = str_replace('|', ' ', $sort);
        $offset = ($page - 1) * $limit;

        $sql = '';

        $operators = [
            ':' => 'LIKE',
            '>' => '>=',
            '<' => '<=',
        ];

        if (!empty($query)) {
            $query = $this->parseSearchParameters($query);

            foreach ($query as $field => $value) {
                @list($field, $operator, $value) = $value;
                $operator = isset($operators[$operator]) ? $operators[$operator] : null;

                if (trim($value) != '') {
                    if ($value == '%%') {
                        $sql .= ' AND (' . $field . ' IS NULL';
                        $sql .= ' OR ' . $field . ' = "")';
                    } else {
                        if (strpos($value, '|')) {
                            $value = explode('|', $value);
                        } else {
                            $value = [$value];
                        }
                    }

                    foreach ($value as $k => $v) {
                        $v = strtolower(str_replace('%', '*', $v));

                        if (!$k) {
                            $sql .= " AND ({$field} {$operator} '{$v}'";
                        } else {
                            $sql .= " OR {$field} {$operator} '{$v}'";
                        }
                    }

                    $sql .= ')';
                }
            }
        }

        if (!empty($nestedQuery)) {
            $nestedQuery = $this->parseSearchParameters($nestedQuery);

            foreach ($nestedQuery as $field => $value) {
                @list($field, $operator, $value) = $value;
                $operator = isset($operators[$operator]) ? $operators[$operator] : null;

                if (trim($value) !== '') {
                    if ($value == '%%') {
                        $sql .= ' AND (nested(' . $field . ') IS NULL';
                        $sql .= ' OR nested(' . $field . ') = "")';
                    } else {
                        if (strpos($value, '|')) {
                            $value = explode('|', $value);
                        } else {
                            $value = [$value];
                        }
                    }

                    foreach ($value as $k => $v) {
                        $v = strtolower(str_replace('%', '*', $v));

                        if (!$k) {
                            $sql .= " AND (nested({$field}) {$operator} '{$v}'";
                        } else {
                            $sql .= " OR nested({$field}) {$operator} '{$v}'";
                        }
                    }

                    $sql .= ')';
                }
            }
        }

        empty($sql) ?: $sql = ' WHERE' . substr($sql, 4);

        $resultsSql = 'SELECT ' . $fields . ' FROM ' . $model . $sql . ' ORDER BY ' . $sort . ($withLimit ? ' LIMIT ' . $offset . ', ' . $limit : '');

        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://' . $this->config->elasticSearch['hosts'][0] . '/_sql?sql=' . urlencode($resultsSql));
        $results = json_decode($response->getBody()->getContents(), true);

        if ($results['hits']['total'] == 0) {
            throw new \Exception('No records were found.');
        }

        $data = [];
        foreach ($results['hits']['hits'] as $result) {
            $result['_source']['is_checked'] = 1;
            if ($filterId != 0) {
                foreach ($checkedIncludes as $key => $value) {
                    if ($result['_source']['id'] == $key) {
                        $result['_source']['is_checked'] = $value;
                    }
                }
            }
            $data[] = $result['_source'];
        }

        /**
         * Sort the response with PHP since elastic sql has problesm
         * @todo check this shit
         */
        $dataSort = [];
        $sortFields = explode(',', $sort);
        foreach ($sortFields as $key => $sortField) {
            $field = explode(' ', $sortField);
            $dataSort[] = $field[0];
            $dataSort[] = isset($field[1]) && strtolower($field[1]) == 'asc' ? SORT_ASC : SORT_DESC;
        }

        //get the data sorted
        $data = $this->orderBy($data, $dataSort);

        $pages = $this->paginate(
            $data,
            $results['hits']['total'],
            $page,
            $offset,
            $limit
        );

        return $this->response($pages);
    }

    /**
     * Order the search by it id
     * we do order in php since its faster then using mysql
     *
     * @param  array $data
     * @param  array $args
     * @return array
     */
    protected function orderBy($data, $args): array
    {
        /**
         * naveget the response and sort it by the field
         */
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $fieldPath = explode('.', $field);
                    $fieldVal = $row;
                    foreach ($fieldPath as $value) {
                        if (is_array($fieldVal) && array_key_exists($value, $fieldVal)) {
                            $fieldVal = $fieldVal[$value];
                        } else {
                            $fieldVal = '';
                        }
                    }
                    $tmp[$key] = $fieldVal;
                }
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }

    /**
     * Paginate array
     *
     * @param      array   $items       The items
     * @param      integer  $totalItems  The total items
     * @param      integer  $page      The page
     * @param      integer  $offset      The offset
     * @param      integer  $limit       The limit
     *
     * @return     \stdClass
     */
    protected function paginate($items, $totalItems, $page, $offset, $limit): stdClass
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        $totalPages = ceil($totalItems / $limit);

        $next = $page < $totalPages ? $page + 1 : '';
        $prev = $page > 1 ? $page - 1 : '';

        $pagination = new stdClass();
        $pagination->total = (int) $totalItems;
        $pagination->per_page = (int) $limit;
        $pagination->current_page = (int) $page;
        $pagination->last_page = $totalPages;
        $pagination->next_page_url = '';
        $pagination->prev_page_url = '';
        $pagination->from = $offset + 1;
        $pagination->to = $offset + $limit;

        if ($pagination->to > $totalItems) {
            $pagination->to = $totalItems;
        }

        $pagination->data = $items;

        $pagination->total_pages = $totalPages;

        return $pagination;
    }

    /**
     * Parse the search query, overwrite the parent furnction
     *
     * @param  string $unparsed
     * @return array
     */
    protected function parseSearchParameters($unparsed): array
    {
        // $unparsed = urldecode($unparsed);
        // Strip parens that come with the request string
        $unparsed = trim($unparsed, '()');

        // Now we have an array of "key:value" strings.
        $splitFields = explode(',', $unparsed);
        $mapped = [];

        // Split the strings at their colon, set left to key, and right to value.
        foreach ($splitFields as $field) {
            $splitField = preg_split('#(:|>|<)#', $field, -1, PREG_SPLIT_DELIM_CAPTURE);

            // TEMP: Fix for strings that contain semicolon
            if (count($splitField) > 3) {
                $splitField[2] = implode('', array_splice($splitField, 2));
            }

            $mapped[] = $splitField;
        }

        return $mapped;
    }
}
