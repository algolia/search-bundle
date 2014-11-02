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

    public static $neededEntityTypes = [
        'ProductForReindexTest'
    ];

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

    public function safeReindex($use_entity_alias)
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
        $this->getEntityManager()->clear();

        $entityName = $use_entity_alias ?
            'AlgoliaSearchSymfonyDoctrineBundle:ProductForReindexTest' :
            'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForReindexTest'
        ;

        $input = new ArrayInput(array(
            'entityName' => $entityName,
            '--batch-size' => 10,
            '--sync' => ' '
        ));

        $this->command->run($input, new ConsoleOutput());

        $resuts = $this->getIndexer()->search('ProductForReindexTest', 'Product');
        $this->assertEquals($n, $resuts['nbHits']);
    }

    public function testSafeReindex()
    {
        $this->safeReindex($use_entity_alias = false);
    }

    public function testReindexCommandUnderstandsEntityAliases()
    {
        $this->safeReindex($use_entity_alias = true);
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

        $this->assertEquals($n, $this->getEntityManager()->getUnitOfWork()->size(), 'Size of unit of work is not correct.');

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $nInDB = $this->getEntityManager()->createQueryBuilder()
        ->select('count(e)')->from('Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForReindexTest', 'e')
        ->getQuery()
        ->getSingleScalarResult();

        $this->assertEquals($n, $nInDB, 'Number of items in the db is not correct.');

        $input = new ArrayInput(array(
            'entityName' => 'Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\ProductForReindexTest',
            '--batch-size' => 10,
            '--sync' => ' ',
            '--unsafe' => ' '
        ));

        $nIndexed = $this->command->run($input, new ConsoleOutput());

        $this->assertEquals($n, $nIndexed, 'Indexer did not reindex the expected number of items.');

        $resuts = $this->getIndexer()->search('ProductForReindexTest', 'Product');
        $this->assertEquals($n, $resuts['nbHits']);
    }
}
