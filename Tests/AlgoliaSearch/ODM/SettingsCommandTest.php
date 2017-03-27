<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\ODM;

use Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch\SettingsCommandTest as BaseSettingsCommandTest;
use Algolia\AlgoliaSearchBundle\Tests\Traits\ODMTestTrait;

class SettingsCommandTest extends BaseSettingsCommandTest
{
    use ODMTestTrait;

    protected function getCommandOptions()
    {
        return ['--dm' => 'default'];
    }
}
