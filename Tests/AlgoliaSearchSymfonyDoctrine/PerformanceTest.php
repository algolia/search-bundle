<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class PerformanceTest extends BaseTest
{
    public function setUp()
    {
        static::setupDatabase();
        parent::setUp();
    }

    public function makeProduct($i, $class)
    {
        $product = new $class();
        $product
        ->setName("Product $i")
        ->setShortDescription("Product $i is a cool product with " . $i*$i . "features.")
        ->setDescription("This is product n°$i")
        ->setPrice(3*($i % 5))
        ->setRating(1 + ($i + 5) % 10);

        return $product;
    }

    /**
     * @large
     */
    public function testOverheadOfAlgoliaLayerNotToHighFlushAfterEachOperation()
    {
        $productCount = 200;

        $tAlgolia = microtime(true);
        for ($i = 0; $i < $productCount; $i += 1) {
            $product = $this->makeProduct(
                $i,
                'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForPerformanceTest'
            );
            $this->persistAndFlush($product);
            $product->setName('Paranoid Android');
            $this->persistAndFlush($product);
            $this->removeAndFlush($product);
        }
        $tAlgolia = microtime(true) - $tAlgolia;

        // Recreate the database, but do not wire our event listener
        self::setupDatabase($noAlgolia = true);

        $tNative = microtime(true);
        for ($i = 0; $i < $productCount; $i += 1) {
            $product = $this->makeProduct(
                $i,
                'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductWithNoAlgoliaAnnotation'
            );
            $this->persistAndFlush($product);
            $product->setName('Paranoid Android');
            $this->persistAndFlush($product);
            $this->removeAndFlush($product);
        }
        $tNative = microtime(true) - $tNative;


        $delta = 100 * ($tAlgolia / $tNative - 1);

        $this->assertLessThan(15, $delta, 'Wooops! The Algolia layer has become too slow - if this test keeps failing, do something!');
    }

    /**
     * @large
     */
    public function testOverheadOfAlgoliaLayerNotTooHighBatchFlush()
    {
        $productCount = 1000;

        $algoliaProducts = [];
        for ($i = 0; $i < $productCount; $i += 1) {
            $algoliaProducts[] = $this->makeProduct(
                $i,
                'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForPerformanceTest'
            );
        }

        $nativeProducts = [];
        for ($i = 0; $i < $productCount; $i += 1) {
            $nativeProducts[] = $this->makeProduct(
                $i,
                'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductWithNoAlgoliaAnnotation'
            );
        }

        array_map([$this->getEntityManager(), 'persist'], $algoliaProducts);
        $tAlgolia = microtime(true);
        $this->getEntityManager()->flush();
        $tAlgolia = microtime(true) - $tAlgolia;

        // Sanity check: see if our indexer is well wired into the thing.
        // We're not checking that there are $productCount creations because
        // since there is conditional indexing, everything will not be indexed.
        // Helf the count is enough four our purpose.
        $this->assertGreaterThan(
            $productCount / 2,
            count($this->getIndexer()->creations['ProductForPerformanceTest_dev']),
            'Indexer did not record the right number of creations.'
        );

        // Recreate the database, but do not wire our event listener
        self::setupDatabase($noAlgolia = true);

        array_map([$this->getEntityManager(), 'persist'], $nativeProducts);
        $tNative = microtime(true);
        $this->getEntityManager()->flush();
        $tNative = microtime(true) - $tNative;


        $delta = 100 * ($tAlgolia / $tNative - 1);


        // Setting a more conservative value for the threshold here, because
        // careful real profiling with xdebug reveals that
        // our functions are in fact responsible of less than 3% of the overhead.
        // Bottom line is, in app timing is not always the best profiler, and this
        // is more here as a safeguard than a true benchmark.
        $this->assertLessThan(
            35,
            $delta,
            sprintf(
                'Wooops! The Algolia layer has become too slow (%1$fs >> %2$fs)- if this test keeps failing, do something!',
                $tAlgolia,
                $tNative
            )
        );
    }
}
