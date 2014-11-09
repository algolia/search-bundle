<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class ChangeDetectionTest extends BaseTest
{
    public static $neededEntityTypes = [
        'Product',
        'ProductWithIndexedMethod'
    ];

    public function testNewProductWouldBeInserted()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\Product();
        $product->setName('Precision Watch');

        $this->assertEquals(array(), $indexer->creations);
        $this->persistAndFlush($product);
        $this->assertEquals(
            array(
                metaenv('Product_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['id' => 1]),
                        'name' => 'Precision Watch'
                    )
                )
            ),
            $indexer->creations
        );

        return $product;
    }

    /**
     * @depends testNewProductWouldBeInserted
     */
    public function testExistingProductWouldBeUpdated($product)
    {
        $indexer = $this->getIndexer();

        $product->setName('Yet Another Precision Watch');
        $this->persistAndFlush($product);
        $this->assertEquals(
            array(
                metaenv('Product_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['id' => $product->getId()]),
                        'name' => 'Yet Another Precision Watch'
                    )
                )
            ),
            $indexer->updates
        );
    }

    public function testNewProductWithIndexedMethodWouldBeInserted()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\ProductWithIndexedMethod();
        $product->setName('Precision Watch');

        $this->assertEquals(array(), $indexer->creations);
        $this->persistAndFlush($product);
        $this->assertEquals(
            array(
                metaenv('ProductWithIndexedMethod_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['id' => 1]),
                        'name' => 'Precision Watch',
                        'yoName' => 'YO Precision Watch'
                    )
                )
            ),
            $indexer->creations
        );

        return $product;
    }

    /**
     * @depends testNewProductWithIndexedMethodWouldBeInserted
     */
    public function testExistingProductWithIndexedMethodWouldBeUpdated($product)
    {
        $indexer = $this->getIndexer();

        $this->assertEquals(array(), $indexer->updates);

        $product->setName('Yet Another Precision Watch');
        $this->persistAndFlush($product);
        $this->assertEquals(
            array(
                metaenv('ProductWithIndexedMethod_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['id' => $product->getId()]),
                        'name' => 'Yet Another Precision Watch',
                        'yoName' => 'YO Yet Another Precision Watch'
                    )
                )
            ),
            $indexer->updates
        );
    }

    public function testExistingProductWouldNotBeUpdatedWhenUninterestingAttributesAreChanged()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\ProductWithIndexedMethod();
        $product->setName('Another Precision Watch');
        $this->persistAndFlush($product);

        $indexer->reset();

        $product->setPrice(42);
        $this->persistAndFlush($product);

        $this->assertEquals(
            array(),
            $indexer->updates
        );
    }

    public function testExistingProductWouldBeDeleted()
    {
        $indexer = $this->getIndexer();

        $product = new Entity\Product();
        $product->setName('This Product Is Doomed To Die');
        $this->persistAndFlush($product);
        $id = $product->getId();

        $indexer->reset();
        $this->assertEquals(array(), $indexer->deletions);
        $this->removeAndFlush($product);
        $this->assertEquals(
            array(
                metaenv('Product_dev') => array(
                    $this->getObjectID(['id' => $id])
                )
            ),
            $indexer->deletions
        );
    }
}
