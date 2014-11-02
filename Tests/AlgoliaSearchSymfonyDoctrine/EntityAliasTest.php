<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class EntityAliasTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public static $neededEntityTypes = [
        'ProductForAlgoliaIntegrationTest'
    ];

    public function setUp()
    {
        parent::setUp();
        $this->getIndexer()->deleteIndex('ProductForAlgoliaIntegrationTest');
        $this->getIndexer()->waitForAlgoliaTasks();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->getIndexer()->deleteIndex('ProductForAlgoliaIntegrationTest');
        $this->getIndexer()->waitForAlgoliaTasks();
    }

    public function testNativeSearchByEntityAlias()
    {
        $product = new Entity\ProductForAlgoliaIntegrationTest();

        $product
        ->setName('My First Product')
        ->setShortDescription('Is Awesome.')
        ->setDescription('Let me index it for you.')
        ->setPrice(9.99)
        ->setRating(10);

        $this->persistAndFlush($product);

        $this
        ->getEntityManager()
        ->getRepository('AlgoliaSearchSymfonyDoctrineBundle:ProductForAlgoliaIntegrationTest')
        ->findOneBy(['name' => 'My First Produdct']);

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->nativeSearch(
            $this->getEntityManager(),
            'AlgoliaSearchSymfonyDoctrineBundle:ProductForAlgoliaIntegrationTest',
            'My First Product'
        );

        $this->assertEquals(1, $results['nbHits']);
    }
}
