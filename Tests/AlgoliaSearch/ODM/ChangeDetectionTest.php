<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ChangeDetectionTest as BaseChangeDetectionTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;

class ChangeDetectionTest extends BaseChangeDetectionTest
{
    use ODMTestTrait;

    public function testProductWithCompositePrimaryKeyWouldBeInserted()
    {
        $this->markTestSkipped('Test is not relevant on MongoDB');
    }
}
