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

        // since 6.x+ we need to use POST
        $response = $client->post($this->getDriverUrl(), [
            $this->getPostKey() => trim($sql),
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ]);

        //get the response in a array
        $results = json_decode(
            $response->getBody()->getContents(),
            true
        );

        if ($results['hits']['total'] == 0) {
            return [];
        }

        return $this->getResults($results);
    }

    /**
     * Reading the env variables determine
     * the POST host URl.
     *
     * @return string
     */
    protected function getDriverUrl(): string
    {
        switch (getenv('ELASTIC_DRIVE')) {
            case 'opendistro':
                $url = '/_opendistro/_sql';
                break;
            default:
                $url = '/_nlpcn/sql';
                break;
        }

        return $url;
    }

    /**
     * Given the driver config , determine the post Key.
     *
     * @return string
     */
    protected function getPostKey(): string
    {
        switch (getenv('ELASTIC_DRIVE')) {
            case 'opendistro':
                $key = 'query';
                break;
            default:
                $key = 'sql';
                break;
        }

        return $key;
    }

    /**
     * Given the elastic results, return only the data.
     *
     * @param array $results
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
