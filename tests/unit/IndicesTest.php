<?php

use Baka\Elasticsearch\IndexBuilderStructure;

class IndicesTest extends PhalconUnitTestCase
{
    /**
     * Test the creation of a normal index based on a model extending
     * from the Indices class of the package
     *
     * @return void
     */
    public function testCreateNormalIndex()
    {
        $elasticsearch = new IndexBuilderStructure();

        $indices = new Indices();
        $elasticsearch->createIndices('Indices');
    }

    /**
     * Inset document test
     *
     * @return void
     */
    public function testInsertDocumentToIndex()
    {
        $elasticsearch = new IndexBuilderStructure();
        $indices = new Indices();

        $elasticsearch->indexDocument($indices);
    }

    /**
     * Inset document test
     *
     * @return void
     */
    public function testDeletetDocumentToIndex()
    {
        $elasticsearch = new IndexBuilderStructure();
        $indices = new Indices();

        $elasticsearch->deleteDocument($indices);
    }

    /**
     * this runs before everyone
     */
    protected function setUp()
    {
        $this->_getDI();
    }

    protected function tearDown()
    {
    }
}
