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
        $elasticsearch->indexDocument($indices);
        /*
                $reflector = $this->_getDI()->getAnnotations()->get(get_class($indices));

                $properties = $reflector->getPropertiesAnnotations();

                foreach ($properties as $property) {
                    print_R($property->get('ES\Property')->getArguments());
                } */
        die();
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
