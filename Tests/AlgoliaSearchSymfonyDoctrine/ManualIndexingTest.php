<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class ManualIndexingTest extends BaseTest
{
    public static $neededEntityTypes = [
        'ProductWithoutAutoIndex'
    ];

    static $nProducts = 100;

    public function cleanDatabaseAndMakeProducts()
    {
        parent::setupDatabase();

        $em = $this->getEntityManager();

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

        $indexer->getManualIndexer($this->getEntityManager())->index($product);

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

        $indexer->getManualIndexer($this->getEntityManager())->unIndex($product);
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

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getEntityManager())->index(
            'AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex',
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

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getEntityManager())->unIndex(
            'AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex',
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

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getEntityManager())->index(
            'AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getEntityManager()->createQuery('SELECT p FROM AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
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

        $nUnIndexed = $this->getIndexer()->getManualIndexer($this->getEntityManager())->unIndex(
            'AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getEntityManager()->createQuery('SELECT p FROM AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
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

        $nProcessed = $this->getIndexer()->getManualIndexer($this->getEntityManager())->reIndex(
            'AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex',
            [
                'batchSize' => 27,
                'query' => $this->getEntityManager()->createQuery('SELECT p FROM AlgoliaSearchSymfonyDoctrineBundle:ProductWithoutAutoIndex p WHERE p.rating = 9')
            ]
        );

        $this->getIndexer()->waitForAlgoliaTasks();

        $results = $this->getIndexer()->rawSearch('ProductWithoutAutoIndex', 'Product');

        $this->getIndexer()->deleteIndex('ProductWithoutAutoIndex')->waitForAlgoliaTasks();

        $this->assertEquals(10, $nProcessed);
        $this->assertEquals(10, $results->getNbHits());
    }
}
