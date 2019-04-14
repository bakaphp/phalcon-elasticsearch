<?php

namespace Baka\Elasticsearch;

use Exception;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    private $host;

    /**
     * Set the host.
     *
     * @param string $host
     * @return void
     */
    public function __construct(string $host)
    {
        $this->host = $host;
    }

    /**
     * Given a SQL search the elastic indices.
     *
     * @param string $sql
     * @return void
     */
    public function findBySql(string $sql): array
    {
        $client = new GuzzleClient([
            'base_uri' => $this->host,
        ]);

        // since 6.x we need to use POST
        $response = $client->post('/_sql', [
            'body' => trim($sql),
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ]);

        //get the response in a array
        $results = json_decode($response->getBody()->getContents(), true);

        if ($results['hits']['total'] == 0) {
            return [];
        }

        return $this->getResults($results);
    }

    /**
     * Given the elastic results, return only the data.
     *
     * @param array $resonse
     * @return array
     */
    private function getResults(array $results): array
    {
        $data = [];
        foreach ($results['hits']['hits'] as $result) {
            $data[] = $result['_source'];
        }

        return $data;
    }
}
