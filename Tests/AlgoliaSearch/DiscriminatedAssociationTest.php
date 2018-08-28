<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity\ProductWithDiscriminatedAssociation;

abstract class DiscriminatedAssociationTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public function tearDown()
    {
        parent::tearDown();
        $this->getIndexer()->deleteIndex('ProductWithDiscriminatedEmbedTest');
        $this->getIndexer()->waitForAlgoliaTasks();
    }

    public function testManualIndexingWithDiscriminatedEntity()
    {
        $document = new ProductWithDiscriminatedAssociation();
        static::staticGetObjectManager()->persist($document);

        $nIndexed = $this->getIndexer()->getManualIndexer($this->getObjectManager())->index($document);

        $this->assertEquals(
            1,
            $nIndexed
        );
    }
}
