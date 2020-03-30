<?php

use Baka\Elasticsearch\Contracts\CustomFiltersSchemaTrait;
use Baka\Elasticsearch\IndexBuilderStructure;

class CustomFilterSchemaTest extends PhalconUnitTestCase
{
    use CustomFiltersSchemaTrait;

    /**
     * Emulate DI.
     *
     * @var elastic
     */
    protected $elastic;

    /**
     * Test the creation of a normal index based on a model extending
     * from the Indices class of the package.
     *
     * @return void
     */
    public function testFilterSchema()
    {
        $this->elastic = $this->getDI()->getElastic();

        $mapping = $this->getSchema('leads');

        $this->assertTrue(!empty($mapping));
        $this->assertTrue(array_search('id', $mapping) > 0);
    }
}
