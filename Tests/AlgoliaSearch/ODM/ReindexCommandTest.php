<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ReindexCommandTest as BaseReindexCommandTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;

class ReindexCommandTest extends BaseReindexCommandTest
{
    use ODMTestTrait;

    protected function getCommandOptions()
    {
        return ['--dm' => 'default'];
    }
}
