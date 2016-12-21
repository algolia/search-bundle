<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;

abstract class EntityAliasTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

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

    public function testSearchByEntityAlias()
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

        $result = $this->getIndexer()->search(
            $this->getObjectManager(),
            'AlgoliaSearchBundle:ProductForAlgoliaIntegrationTest',
            'My First Product'
        );

        $this->assertEquals(1, $result->getNbHits());
    }
}
