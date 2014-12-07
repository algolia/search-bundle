<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class AlgoliaIntegrationTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public static $neededEntityTypes = [
        'ProductForAlgoliaIntegrationTest'
    ];

    public function tearDown()
    {
        parent::tearDown();
        $this->getIndexer()->deleteIndex('ProductForAlgoliaIntegrationTest');
        $this->getIndexer()->deleteIndex('MongoProduct');
        $this->getIndexer()->waitForAlgoliaTasks();
    }

    public function testNewProductIsIndexedAndRetrieved()
    {
        $product = new Entity\ProductForAlgoliaIntegrationTest();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('ProductForAlgoliaIntegrationTest', 'My First Product');

        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals(0, $results->getPage());
        $this->assertEquals(1, $results->getNbPages());
        $this->assertEquals(20, $results->getHitsPerPage());
        $this->assertGreaterThan(0, $results->getProcessingTimeMS());
        $this->assertGreaterThan(0, $results->getProcessingTimeMS());
        $this->assertEquals('My First Product', $results->getQuery());
        $this->assertEquals('query=My+First+Product', $results->getParams());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);
    }

    public function testNewMongoProductIsIndexedAndRetrieved()
    {
        $product = new Entity\MongoProduct();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('MongoProduct', 'My First Product');

        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals(0, $results->getPage());
        $this->assertEquals(1, $results->getNbPages());
        $this->assertEquals(20, $results->getHitsPerPage());
        $this->assertGreaterThan(0, $results->getProcessingTimeMS());
        $this->assertGreaterThan(0, $results->getProcessingTimeMS());
        $this->assertEquals('My First Product', $results->getQuery());
        $this->assertEquals('query=My+First+Product', $results->getParams());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);
    }

    public function testUpdatedProductIsIndexedAndRetrieved()
    {
        $product = new Entity\ProductForAlgoliaIntegrationTest();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $product->setName('Totally Different Name.');
        $this->persistAndFlush($product);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('ProductForAlgoliaIntegrationTest', 'Totally Different Name.');

        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);
        $this->assertEquals('Totally Different Name.', $results->getHits()[0]['name']);
    }

    public function testUpdatedMongoProductIsIndexedAndRetrieved()
    {
        $product = new Entity\MongoProduct();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $product->setName('Totally Different Name.');
        $this->persistAndFlush($product);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('MongoProduct', 'Totally Different Name.');

        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);
        $this->assertEquals('Totally Different Name.', $results->getHits()[0]['name']);
    }

    public function testProductIsUnindexed()
    {
        $product = new Entity\ProductForAlgoliaIntegrationTest();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        // Check that the product is indexed!
        $this->getIndexer()->waitForAlgoliaTasks();
        $results = $this->getIndexer()->rawSearch('ProductForAlgoliaIntegrationTest', 'My First Product');
        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);

        $this->removeAndFlush($product);
        $this->getIndexer()->waitForAlgoliaTasks();
        $results = $this->getIndexer()->rawSearch('ProductForAlgoliaIntegrationTest', 'My First Product');
        $this->assertEquals(0, $results->getNbHits());
    }

    public function testMongoProductIsUnindexed()
    {
        $product = new Entity\MongoProduct();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        // Check that the product is indexed!
        $this->getIndexer()->waitForAlgoliaTasks();
        $results = $this->getIndexer()->rawSearch('MongoProduct', 'My First Product');
        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results->getHits()[0]['objectID']);

        $this->removeAndFlush($product);
        $this->getIndexer()->waitForAlgoliaTasks();
        $results = $this->getIndexer()->rawSearch('MongoProduct', 'My First Product');
        $this->assertEquals(0, $results->getNbHits());
    }

    public function testMultipleInserts()
    {
        $names = [];

        $n = 10;
        for ($i = 0; $i < $n; $i += 1) {
            $product = new Entity\ProductForAlgoliaIntegrationTest();

            $name = 'Product Number '.$i;
            $names[] = $name;

            $product
            ->setName($name)
            ->setPrice(1 + $i)
            ->setRating($i);

            $this->getEntityManager()->persist($product);
        }

        $this->getEntityManager()->flush();
        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('ProductForAlgoliaIntegrationTest', 'Product Number');
        $this->assertEquals($n, $results->getNbHits());

        $namesReturned = array_map(function ($p) {
            return $p['name'];
        }, $results->getHits());

        sort($namesReturned);
        sort($names);

        $this->assertEquals($names, $namesReturned);
    }

    public function testSearch()
    {
        $product = new Entity\ProductForAlgoliaIntegrationTest();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->search(
            $this->getEntityManager(),
            'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForAlgoliaIntegrationTest',
            'My First Product'
        );

        $this->assertEquals(1, $results->getNbHits());
        $this->assertEquals(
            'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForAlgoliaIntegrationTest',
            get_class($results->getHit(0))
        );
        $this->assertEquals(
            'My First Product',
            $results->getHit(0)->getName()
        );
    }
}
