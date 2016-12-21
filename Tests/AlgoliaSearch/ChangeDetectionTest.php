<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;

abstract class ChangeDetectionTest extends BaseTest
{
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

    public function testProductWithCompositePrimaryKeyWouldBeInserted()
    {
        $product = new Entity\ProductWithCompositePrimaryKey();

        $product
        ->setName('.the .product')
        ->setPrice(10)
        ->setShortDescription('Coolest demo from farbrausch.')
        ->setDescription('Just watch https://www.youtube.com/watch?v=Y3n3c_8Nn2Y.')
        ->setRating(10);

        $this->persistAndFlush($product);

        $this->assertEquals(
            array(
                metaenv('ProductWithCompositePrimaryKey_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['name' => '.the .product', 'price' => 10]),
                        'name' => '.the .product',
                    )
                )
            ),
            $this->getIndexer()->creations
        );

        return $product;
    }

    /**
     * @depends testProductWithCompositePrimaryKeyWouldBeInserted
     */
    public function testChangeOfCompositePrimaryKeyLeadsToUndindexAndReindex($product)
    {
        /**
         * This one is a bit special:
         *
         * If a product has a composite primary key, updating a field from the primary key
         * will actually be equivalent to deleting the product and inserting a new one, which
         * will have a different objectID.
         *
         * So in this case, what should happen is:
         * - old product is unindexed from Algolia
         * - updated product is inserted into Algolia index as a new entity
         * - no update is performed Algolia side, just one delete and one insert
         */


        $product->setPrice(7);
        $this->persistAndFlush($product);

        // convince ourselves that doctrine DOES delete the old product
        $oldProduct = $this->getObjectManager()
        ->getRepository('AlgoliaSearchBundle:ProductWithCompositePrimaryKey')
        ->findOneBy(['name' => '.the .product', 'price' => 10]);
        $this->assertEquals(null, $oldProduct);

        // check "new" product is indexed
        $this->assertEquals(
            array(
                metaenv('ProductWithCompositePrimaryKey_dev') => array(
                    array(
                        'objectID' => $this->getObjectID(['name' => '.the .product', 'price' => 7]),
                        'name' => '.the .product',
                    )
                )
            ),
            $this->getIndexer()->creations
        );

        // check "old" product is unindexed
        $this->assertEquals(
            array(
                metaenv('ProductWithCompositePrimaryKey_dev') => array(
                    $this->getObjectID(['name' => '.the .product', 'price' => 10])
                )
            ),
            $this->getIndexer()->deletions
        );

        // check we don't try to update anything
        $this->assertEquals([], $this->getIndexer()->updates);
    }

    public function testNothingHappensToAProductNotKnownToAlgolia()
    {
        $product = new Entity\ProductWithNoAlgoliaAnnotation();
        $product->setName('a')->setPrice(9.99)->setDescription('b')->setShortDescription('c')->setRating(1);
        $this->persistAndFlush($product);
        $this->assertEquals([], $this->getIndexer()->creations);
        $this->assertEquals([], $this->getIndexer()->updates);
        $this->assertEquals([], $this->getIndexer()->deletions);
    }

    public function testCustomAlgoliaNamesAreTakenIntoAccount()
    {
        $product = new Entity\ProductWithCustomAttributeNames();
        $product->setName('Hello World.');
        $this->persistAndFlush($product);
        $this->assertEquals([
            metaenv('nonDefaultIndexName_dev') => [
                [
                    'objectID' => $this->getObjectID(['id' => $product->getId()]),
                    'nonDefaultAttributeName' => 'Hello World.'
                ]
            ]
        ], $this->getIndexer()->creations);
    }
}
