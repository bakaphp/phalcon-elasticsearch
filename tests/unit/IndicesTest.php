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
        $elasticsearch::createIndices('Indices');
    }

    /**
     * Test the creation of a index base on a class and specify the name
     *
     * @return void
     */
    public function testCreateNormalIndexWithSpecifiedName()
    {
        $elasticsearch = new IndexBuilderStructure();

        $indices = new Indices();
        $elasticsearch::setIndexName('MyManualIndexName');
        $elasticsearch::createIndices('Indices');
    }

    /**
     * Inset document test normal
     *
     * @return void
     */
    public function testInsertDocumentToIndex()
    {
        $elasticsearch = new IndexBuilderStructure();
        $indices = new Indices();
        //$elasticsearch::setIndexName('Indices');
        $elasticsearch::indexDocument($indices);
    }

    /**
     * Inset document test with specified name
     *
     * @return void
     */
    public function testInsertDocumentToIndexWithSpecifiedName()
    {
        $elasticsearch = new IndexBuilderStructure();
        $indices = new Indices();
        $elasticsearch::setIndexName('MyManualIndexName');
        $elasticsearch::indexDocument($indices);
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
        // $elasticsearch::setIndexName('Indices');
        $elasticsearch::deleteDocument($indices);
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
