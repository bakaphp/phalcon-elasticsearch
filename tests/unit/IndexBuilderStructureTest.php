<?php

use Baka\Elasticsearch\IndexBuilderStructure;

class IndexBuilderStructureTest extends PhalconUnitTestCase
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
        $document = new Document();
        $elasticsearch::createIndices('Document');
    }

    /**
     * Test the creation of a index base on a class and specify the name
     *
     * @return void
     */
    public function testCreateNormalIndexWithSpecifiedName()
    {
        $elasticsearch = new IndexBuilderStructure();

        $document = new Document();
        $elasticsearch::setIndexName('MyManualIndexName');
        $elasticsearch::createIndices('Document');
    }

    /**
     * Insert document test normal
     *
     * @return void
     */
    public function testInsertDocumentToIndex()
    {
        $vehicle = new Vehicle();
        $vehicle->setId(2);
        $vehicle->setDate('2018-01-02');
        $vehicle->setName('wtf');

        $photo1 = new Photo();
        $photo1->setName('test');
        $photo1->setUrl('http://mctekk.com');
        $photo1->addVehicle($vehicle);

        $photo2 = new Photo();
        $photo2->setName('test');
        $photo2->setUrl('http://mctekk.com');
        $photo2->addVehicle($vehicle);
        $photo2->addVehicle($vehicle);

        $entity = new Entity();
        $entity->setId(1);
        $entity->setDescription('tetada');
        $entity->setDate('2018-01-01');
        $entity->setMoney(10.1);
        $entity->setAnotherMoney(10.1);
        $entity->addPhoto($photo1);
        $entity->addPhoto($photo2);

        $elasticsearch = new IndexBuilderStructure();
        $document = new Document();
        $document->setEntity($entity);

        $elasticsearch::indexDocument($document);
    }

    /**
     * Insert document test with specified name
     *
     * @return void
     */
    public function testInsertDocumentToIndexWithSpecifiedName()
    {
        $vehicle = new Vehicle();
        $vehicle->setId(2);
        $vehicle->setDate('2018-01-02');
        $vehicle->setName('wtf');

        $photo1 = new Photo();
        $photo1->setName('test');
        $photo1->setUrl('http://mctekk.com');
        $photo1->addVehicle($vehicle);

        $photo2 = new Photo();
        $photo2->setName('test');
        $photo2->setUrl('http://mctekk.com');
        $photo2->addVehicle($vehicle);
        $photo2->addVehicle($vehicle);

        $entity = new Entity();
        $entity->setId(1);
        $entity->setDescription('tetada');
        $entity->setDate('2018-01-01');
        $entity->setMoney(10.1);
        $entity->setAnotherMoney(10.1);
        $entity->addPhoto($photo1);
        $entity->addPhoto($photo2);

        $elasticsearch = new IndexBuilderStructure();
        $document = new Document();
        $elasticsearch::setIndexName('MyManualIndexName');
        $document->setEntity($entity);
        
        $elasticsearch::indexDocument($document);
    }

    /**
     * Insert document test
     *
     * @return void
     */
    public function testDeletetDocumentFromIndex()
    {
        $entity = new Entity();
        $entity->setId(1);

        $elasticsearch = new IndexBuilderStructure();
        $document = new Document();
        $document->setEntity($entity);

        $elasticsearch::deleteDocument($document);
    }

    /**
     * Insert document test
     *
     * @return void
     */
    public function testBulkIndexDocuments()
    {
        $vehicle = new Vehicle();
        $vehicle->setId(2);
        $vehicle->setDate('2018-01-02');
        $vehicle->setName('wtf');

        $photo1 = new Photo();
        $photo1->setName('test');
        $photo1->setUrl('http://mctekk.com');
        $photo1->addVehicle($vehicle);

        $photo2 = new Photo();
        $photo2->setName('test');
        $photo2->setUrl('http://mctekk.com');
        $photo2->addVehicle($vehicle);
        $photo2->addVehicle($vehicle);

        $entity = new Entity();
        $entity->setId(1);
        $entity->setDescription('tetada');
        $entity->setDate('2018-01-01');
        $entity->setMoney(10.1);
        $entity->setAnotherMoney(10.1);
        $entity->addPhoto($photo1);
        $entity->addPhoto($photo2);

        $entities[] = $entity;

        $elasticsearch = new IndexBuilderStructure();

        $documents = [];

        foreach ($entities as $entity) {
            $document = new Document();
            $document->setEntity($entity);
            $documents[] = $document;
        }

        IndexBuilderStructure::bulkIndexDocuments($documents);
    }

    /**
     * Insert document test
     *
     * @return void
     */
    public function testBulkDeleteDocuments()
    {
        $entity = new Entity();
        $entity->setId(1);

        $entities[] = $entity;

        $elasticsearch = new IndexBuilderStructure();

        $documents = [];

        foreach ($entities as $entity) {
            $document = new Document();
            $document->setEntity($entity);
            $documents[] = $document;
        }

        IndexBuilderStructure::bulkDeleteDocuments($documents);
    }

    /**
     * Insert document test
     *
     * @return void
     */
    public function testGenerateIndexNameFromObject()
    {
        $document = new Document();
        $this->assertSame('document', IndexBuilderStructure::generateIndexNameFromObject($document));
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
