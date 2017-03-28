<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ORM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ReindexCommandTest as BaseReindexCommandTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ORMTestTrait;

class ReindexCommandTest extends BaseReindexCommandTest
{
    use ORMTestTrait;

    protected function getCommandOptions()
    {
        return ['--em' => 'default'];
    }
}
