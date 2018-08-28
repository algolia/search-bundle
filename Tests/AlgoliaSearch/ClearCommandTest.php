<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Command\ClearCommand;
use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

use Algolia\AlgoliaSearchBundle\Command\ReindexCommand;

abstract class ClearCommandTest extends BaseTest
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

        $productWithNoAlgoliaAnnotation = new Entity\ProductWithNoAlgoliaAnnotation();
        $productWithNoAlgoliaAnnotation
        ->setName('Do not index')
        ->setPrice(10)
        ->setDescription('This product must not be indexed')
        ->setShortDescription('Product no index')
        ->setRating(5);
        $em->persist($productWithNoAlgoliaAnnotation);

        $em->flush();
        $em->clear();
    }

    public function setUp()
    {
        parent::setUp();

        global $kernel;
        $app = new Application($kernel);
        $this->clearCommand = new ClearCommand();
        $app->add($this->clearCommand);
        $this->reindexCommand = new ReindexCommand();
        $app->add($this->reindexCommand);
    }

    public function testClearBySkippingNonAlgoliaMappedEntities()
    {
        $input = new ArrayInput($this->getCommandOptions() + ['entityName' => 'Algolia\AlgoliaSearchBundle\Tests\Entity\ProductForReindexTest']);
        $this->reindexCommand->run($input, new NullOutput());
        $this->getIndexer()->waitForAlgoliaTasks();

        $result = $this->getIndexer()->rawSearch('ProductForReindexTest', 'Product');
        $this->assertEquals(static::$nProducts, $result->getNbHits());

        $input = new ArrayInput($this->getCommandOptions());
        $bufferedOutput = new BufferedOutput();
        $this->clearCommand->run($input, $bufferedOutput);

        $this->assertRegExp('/\d+ entities cleared/', $bufferedOutput->fetch());
    }
}
