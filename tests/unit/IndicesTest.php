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
        $elasticsearch::createIndices($indices);
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
        $elasticsearch::createIndices($indices);
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
        $results = $elasticsearch::indexDocument($indices);
        $this->assertTrue(in_array($results['result'], ['updated' , 'created']));

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
        $results = $elasticsearch::indexDocument($indices);

        $this->assertTrue(in_array($results['result'], ['updated' , 'created']));
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
        $indices->setId(1);
        $results = $elasticsearch::deleteDocument($indices);

        $this->assertTrue($results['result'] == 'deleted');
    }
}
