<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ORM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ClearCommandTest as BaseClearCommandTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ORMTestTrait;

class ClearCommandTest extends BaseClearCommandTest
{
    use ORMTestTrait;

    protected function getCommandOptions()
    {
        return ['--em' => 'default'];
    }
}
