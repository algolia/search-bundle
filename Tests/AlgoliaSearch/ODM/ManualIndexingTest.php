<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ManualIndexingTest as BaseManualIndexingTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;
use Doctrine\ODM\MongoDB\DocumentManager;

class ManualIndexingTest extends BaseManualIndexingTest
{
    use ODMTestTrait;

    protected function getQuery()
    {
        return $this->getObjectManager()
            ->createQueryBuilder('AlgoliaSearchBundle:ProductWithoutAutoIndex')
            ->field('rating')
            ->equals(9)
            ->getQuery();
    }
}
