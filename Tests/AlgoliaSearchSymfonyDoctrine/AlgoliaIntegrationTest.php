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
        
        $results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
        
        $this->assertEquals(1, $results['nbHits']);
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results['hits'][0]['objectID']);
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

        $results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'Totally Different Name.');

        $this->assertEquals(1, $results['nbHits']);
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results['hits'][0]['objectID']);
        $this->assertEquals('Totally Different Name.', $results['hits'][0]['name']);
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
        $results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
        $this->assertEquals(1, $results['nbHits']);
        $this->assertEquals($this->getObjectID(['id' => $product->getId()]), $results['hits'][0]['objectID']);

        $this->removeAndFlush($product);
        $this->getIndexer()->waitForAlgoliaTasks();
        $results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
        $this->assertEquals(0, $results['nbHits']);
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

        $results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'Product Number');
        $this->assertEquals($n, $results['nbHits']);

        $namesReturned = array_map(function ($p) {
            return $p['name'];
        }, $results['hits']);

        sort($namesReturned);
        sort($names);

        $this->assertEquals($names, $namesReturned);
    }

    public function testNativeSearch()
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

        $results = $this->getIndexer()->nativeSearch(
            $this->getEntityManager(),
            'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForAlgoliaIntegrationTest',
            'My First Product'
        );

        $this->assertEquals(1, $results['nbHits']);
        $this->assertEquals(
            'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForAlgoliaIntegrationTest',
            get_class($results['hits'][0])
        );
        $this->assertEquals(
            'My First Product',
            $results['hits'][0]->getName()
        );
    }
}
