<?php

namespace Algolia\AlgoliaSearchBundle\Tests\AlgoliaSearch;

use Algolia\AlgoliaSearchBundle\Command\SettingsCommand;
use Algolia\AlgoliaSearchBundle\Tests\BaseTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Input\ArrayInput;

abstract class SettingsCommandTest extends BaseTest
{
    /**
     * Here we really want to test the full integration
     * and talk with Algolia servers.
     */
    public static $isolateFromAlgolia = false;

    public function setUp()
    {
        parent::setUp();

        global $kernel;
        $app = new Application($kernel);
        $command = new SettingsCommand();
        $app->add($command);

        $this->command = $command;
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::staticGetIndexer()->deleteIndex('ProductForAlgoliaIntegrationTest');
    }

    public function runCommand(array $args = array())
    {
        $input = new ArrayInput(array_merge(['command' => 'algolia:settings'], $args));
        $file = tmpfile();
        $this->command->run($input, new StreamOutput($file));
        fseek($file, 0);
        $output = '';
        while (!feof($file)) {
            $output = fread($file, 4096);
        }
        fclose($file);
        return $output;
    }

    public function testIndexWouldBeCreated()
    {
        $output = $this->runCommand();

        $this->assertContains('We found 1 index(es) that may need updating.', $output);
        $this->assertContains('Found a new local index '.metaenv('ProductForAlgoliaIntegrationTest_dev'), $output);
    }

    /**
     * @depends testIndexWouldBeCreated
     */
    public function testIndexIsCreated()
    {
        $this->runCommand(['--push' => ' ', '--force' => ' ']);

        $output = $this->runCommand();
        
        $this->assertContains('Your local index settings seem to be in sync with the Algolia servers!', $output);
    }

    /**
     * @depends testIndexIsCreated
     */
    public function testSettingsAreCorrectlyPushed()
    {
        $actual = $this->getIndexer()->getIndex(metaenv('ProductForAlgoliaIntegrationTest_dev'))->getSettings();
        $expected = [
            'searchableAttributes'          => [
                'name',
                'price',
                'shortDescription',
                'description',
                'rating',
            ],
            'numericAttributesForFiltering' => [
                'rating',
                'price',
            ],
            'highlightPreTag'               => '<strong>',
            'highlightPostTag'              => '</strong>',
            'replicas'                      => ['test'],
        ];

        // Remove entries added by the API.
        $actual = array_intersect_key($actual, $expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @depends testSettingsAreCorrectlyPushed
     */
    public function testSettingsOfExistingIndexAreUpdated()
    {
        $this->getIndexer()->setIndexSettings(
            'ProductForAlgoliaIntegrationTest',
            [
                'searchableAttributes' => ['price']
            ]
        );
        $this->getIndexer()->waitForAlgoliaTasks();

        $output = $this->runCommand();

        $this->assertContains('We found 1 index(es) that may need updating.', $output);
        $this->assertContains('Local searchableAttributes:', $output);

        $this->runCommand(['--push' => ' ', '--force' => ' ']);
        $output = $this->runCommand();
        $this->assertContains('Your local index settings seem to be in sync with the Algolia servers!', $output);
    }


}
