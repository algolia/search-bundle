<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ORM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ManualIndexingTest as BaseManualIndexingTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ORMTestTrait;

class ManualIndexingTest extends BaseManualIndexingTest
{
    use ORMTestTrait;

    protected function getQuery()
    {
        return $this->getObjectManager()->createQuery('SELECT p FROM AlgoliaSearchBundle:ProductWithoutAutoIndex p WHERE p.rating = 9');
    }
}
