<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class ManualIndexingTest extends BaseTest
{
    public static $neededEntityTypes = [
        'ProductWithoutAutoIndex'
    ];

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
}
