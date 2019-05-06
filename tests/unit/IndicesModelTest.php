<?php

use Baka\Elasticsearch\IndexBuilderStructure;
use Baka\Elasticsearch\Contracts\IndexBuilderTaskTrait;
use Test\Model\Leads;
use Baka\Elasticsearch\Contracts\CustomFiltresSchemaTrait;

class IndicesModelTest extends PhalconUnitTestCase
{
    use IndexBuilderTaskTrait;
    use CustomFiltresSchemaTrait;

    public $config;

    /**
     * Create a index base on a model
     *
     * @return void
     */
    public function testCreateIndiceFromModel()
    {
        $this->elastic = $this->getDI()->getElastic();

        //create index
        $this->createIndexAction([
            'Leads', //model
            '1' //depth
        ]);

        
        $mapping = $this->getSchema('leads');
        
        $this->assertTrue(array_search('id', $mapping) > 0);
    }

    /**
     * Test inserting data to elastic search froma module
     *
     * @return void
     */
    public function testInsertDataFromModel()
    {
        //cli need the config
        $this->config = $this->getDI()->getConfig();
        $this->elastic = $this->getDI()->getElastic();

        $this->insertAction([
            'Leads', //model
            1 , //depth
        ]);

        $lead = Leads::findFirst();
        $params = [
            'index' => 'leads',
            'type' => 'leads',
            'id' => $lead->getId()
        ];
        
        $response = $this->elastic->get($params);
        
        $this->assertTrue($response['_source']['id'] == $lead->getId());
    }
}
