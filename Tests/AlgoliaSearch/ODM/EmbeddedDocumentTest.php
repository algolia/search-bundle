<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Algolia\AlgoliaSearchBundle\Tests\Entity\ProductWithEmbeddedDocument;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;

class EmbeddedDocumentTest extends BaseTest
{
    use ODMTestTrait;

    public function beforeEach()
    {
        $this->getIndexer()->reset();
    }

    public function testPersistingProductOnlyTriggersOneCreation()
    {
        $product = new ProductWithEmbeddedDocument();
        $product
            ->setName('The Ultimate Algolia Userguide')
            ->setShortDescription('Learn to master your search engine and drive sales up!')
            ->setRating(1);

        $this->persistAndFlush($product);
        $this->assertCount(1, $this->getIndexer()->creations);

        return $product;
    }

    public function testUpdatingProductOnlyTriggersOneUpdate()
    {
        $product = new ProductWithEmbeddedDocument();
        $product
            ->setName('The Ultimate Algolia Userguide')
            ->setShortDescription('Learn to master your search engine and drive sales up!')
            ->setRating(1);

        $this->persistAndFlush($product);
        $this->getIndexer()->reset();

        $product
            ->setName('The Ultimate Guide')
            ->setRating(2);

        $this->persistAndFlush($product);
        $this->assertCount(1, $this->getIndexer()->updates);

        return $product;
    }

    public function testDeletingProductOnlyTriggersOneDeletion()
    {
        $product = new ProductWithEmbeddedDocument();
        $product
            ->setName('The Ultimate Algolia Userguide')
            ->setShortDescription('Learn to master your search engine and drive sales up!')
            ->setRating(1);

        $this->persistAndFlush($product);
        $this->getIndexer()->reset();

        $this->removeAndFlush($product);

        $this->assertCount(1, $this->getIndexer()->deletions);

        return $product;
    }
}
