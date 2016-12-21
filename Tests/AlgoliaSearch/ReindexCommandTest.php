<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Command\ReindexCommand;
use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;

abstract class ReindexCommandTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public static $nProducts = 30;

    abstract protected function getCommandOptions();

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $em = static::staticGetObjectManager();

        // Setup our fixtures
        for ($i = 0; $i < static::$nProducts; $i += 1) {
            $product = new Entity\ProductForReindexTest();

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

    public function setUp()
    {
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

    public function reIndex($use_entity_alias, $safe = true)
    {
        $entityName = $use_entity_alias ?
            'AlgoliaSearchBundle:ProductForReindexTest' :
            'Algolia\AlgoliaSearchBundle\Tests\Entity\ProductForReindexTest'
        ;

        $options = $this->getCommandOptions() + [
            'entityName' => $entityName,
            '--batch-size' => 8, // not a divisor of static::$nProducts, on purpose
            '--sync' => ' '
        ];

        if (!$safe) {
            $options['--unsafe'] = ' ';
        }

        $input = new ArrayInput($options);

        $this->command->run($input, new ConsoleOutput());

        $result = $this->getIndexer()->rawSearch('ProductForReindexTest', 'Product');
        $this->assertEquals(static::$nProducts, $result->getNbHits());
    }

    public function testSafeReindex()
    {
        $this->reIndex($use_entity_alias = false, $safe = true);
    }

    public function testReindexCommandUnderstandsEntityAliases()
    {
        $this->reIndex($use_entity_alias = true, $safe = true);
    }

    public function testUnSafeReindex()
    {
        $this->reIndex($use_entity_alias = false, $safe = false);
    }
}
