<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;

abstract class ManualIndexingTest extends BaseTest
{
    public static $nProducts = 100;

    public function cleanDatabaseAndMakeProducts()
    {
        static::setupDatabase();

        $em = $this->getObjectManager();

        for ($i = 0; $i < static::$nProducts; $i += 1) {
            $product = new Entity\ProductWithoutAutoIndex();

            $product
            ->setName("Product $i")
            ->setShortDescription("Product n°$i is cool.")
            ->setDescription("Product n°$i is right before n°".($i+1)." unless it is the last one.")
            ->setPrice(1 + $i % 100) // ensure price is > 0 else IndexIf prevents indexing and it messes up the assertions
            ->setRating($i % 10);

            $em->persist($product);
        }

        $em->flush();
        $em->clear();
    }

    public function testNonAutoIndexedProductIsNotAutomaticallyIndexed()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\ProductWithoutAutoIndex();
        $product->setName('This Product Is Not Auto Indexed');
        $indexer->reset();
        $this->persistAndFlush($product);
        $this->assertEquals(array(), $indexer->creations);
        $this->assertEquals(array(), $indexer->updates);
    }

    public function testNonAutoIndexedProductIsManuallyIndexed()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\ProductWithoutAutoIndex();
        $product
        ->setName('This Product Is Not Auto Indexed, But I\'ll Index It')
        ->setDescription('Yes, I\'m clever like that.');

        $indexer->reset();
        $this->assertEquals(array(), $indexer->updates);
        $this->persistAndFlush($product);
        $this->assertEquals(array(), $indexer->updates);

        $id = $product->getId();

        $indexer->getManualIndexer($this->getObjectManager())->index($product);

        $this->assertEquals(array(), $indexer->deletions);
        $this->assertEquals(array(), $indexer->updates);
        $this->assertEquals(array(
            metaenv('ProductWithoutAutoIndex_dev') => array(
                array(
                    'name' => 'This Product Is Not Auto Indexed, But I\'ll Index It',
                    'objectID' => $this->getObjectID(['id' => $id])
                )
            )
        ), $indexer->creations);

        $indexer->reset();

        $indexer->getManualIndexer($this->getObjectManager())->unIndex($product);
        $this->assertEquals(array(
            metaenv('ProductWithoutAutoIndex_dev') => array(
                $this->getObjectID(['id' => $id])
            )
        ), $indexer->deletions);
        $this->assertEquals(array(), $indexer->updates);
    }

    public function testManualIndexByEntityName()
    {
        $this->cleanDatabaseAndMakeProducts();

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->index(
            'AlgoliaSearchBundle:ProductWithoutAutoIndex',
            ['batchSize' => 27]
        );

        $this->assertEquals(
            static::$nProducts,
            $nIndexed
        );
    }

    public function testManualUnIndexByEntityName()
    {
        $this->cleanDatabaseAndMakeProducts();

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->unIndex(
            'AlgoliaSearchBundle:ProductWithoutAutoIndex',
            ['batchSize' => 27]
        );

        $this->assertEquals(
            static::$nProducts,
            $nIndexed
        );
    }

    public function testManualIndexByQuery()
    {
        $this->cleanDatabaseAndMakeProducts();

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->index(
            'AlgoliaSearchBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getObjectManager()->createQuery('SELECT p FROM AlgoliaSearchBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
            ]
        );

        $this->assertEquals(
            10,
            $nIndexed
        );
    }

    public function testManualUnIndexByQuery()
    {
        $this->cleanDatabaseAndMakeProducts();

        $nUnIndexed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->unIndex(
            'AlgoliaSearchBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getObjectManager()->createQuery('SELECT p FROM AlgoliaSearchBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
            ]
        );

        $this->assertEquals(
            10,
            $nUnIndexed
        );
    }

    public function testReIndexByQuery()
    {
        $this->getIndexer()->isolateFromAlgolia(false);
        $this->getIndexer()->deleteIndex('ProductWithoutAutoIndex')->waitForAlgoliaTasks();

        $nProcessed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->reIndex(
            'AlgoliaSearchBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getObjectManager()->createQuery('SELECT p FROM AlgoliaSearchBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
            ]
        );

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('ProductWithoutAutoIndex', 'Product');

        $this->getIndexer()->deleteIndex('ProductWithoutAutoIndex')->waitForAlgoliaTasks();

        $this->assertEquals(10, $nProcessed);
        $this->assertEquals(10, $results->getNbHits());
    }
}
