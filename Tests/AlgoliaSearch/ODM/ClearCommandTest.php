<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ClearCommandTest as BaseClearCommandTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;

class ClearCommandTest extends BaseClearCommandTest
{
    use ODMTestTrait;

    protected function getCommandOptions()
    {
        return ['--dm' => 'default'];
    }
}
