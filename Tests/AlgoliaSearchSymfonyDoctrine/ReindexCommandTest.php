<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Command\ReindexCommand;

class ReindexCommandTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public function setUp()
    {
        static::setupDatabase();
        parent::setUp();

        global $kernel;
        $app = new Application($kernel);
        $command = new ReindexCommand();
        $app->add($command);

        $this->command = $command;
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->getIndexer()->deleteIndex('ProductForReindexTest');
        $this->getIndexer()->waitForAlgoliaTasks();
    }

    public function testSafeReindex()
    {
        $n = 100;
        for ($i = 0; $i < $n; $i += 1) {
            $product = new Entity\ProductForReindexTest();

            $product
            ->setName("Product $i")
            ->setShortDescription("Product n°$i is cool.")
            ->setDescription("Product n°$i is right before n°".($i+1)." unless it is the last one.")
            ->setPrice(1 + $i % 100) // ensure price is > 0 else IndexIf prevents indexing and it messes up the assertions
            ->setRating($i % 10);

            $this->getEntityManager()->persist($product);
        }

        $this->getEntityManager()->flush();


        $input = new ArrayInput(array(
            'entityName' => 'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForReindexTest',
            '--batch-size' => 10,
            '--sync' => ' '
        ));

        $this->command->run($input, new ConsoleOutput());

        $resuts = $this->getIndexer()->search('ProductForReindexTest', 'Product');
        $this->assertEquals($n, $resuts['nbHits']);
    }

    public function testUnSafeReindex()
    {
        $n = 100;
        for ($i = 0; $i < $n; $i += 1) {
            $product = new Entity\ProductForReindexTest();

            $product
            ->setName("Product $i")
            ->setShortDescription("Product n°$i is cool.")
            ->setDescription("Product n°$i is right before n°".($i+1)." unless it is the last one.")
            ->setPrice(1 + $i % 100) // ensure price is > 0 else IndexIf prevents indexing and it messes up the assertions
            ->setRating($i % 10);

            $this->getEntityManager()->persist($product);
        }

        $this->getEntityManager()->flush();


        $input = new ArrayInput(array(
            'entityName' => 'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForReindexTest',
            '--batch-size' => 10,
            '--sync' => ' ',
            '--unsafe' => ' '
        ));

        $this->command->run($input, new ConsoleOutput());
        $resuts = $this->getIndexer()->search('ProductForReindexTest', 'Product');
        $this->assertEquals($n, $resuts['nbHits']);
    }
}
