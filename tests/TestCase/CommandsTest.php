<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;

class CommandsTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\IndexManager */
    protected $indexManager;
    protected $client;
    protected $om;
    protected $connection;
    protected $application;
    protected $indexName;
    protected $platform;
    protected $index;

    public function setUp()
    {
        parent::setUp();
        $this->indexManager = $this->get('search.index_manager');
        $this->client       = $this->get('search.client');
        $this->om           = $this->get('doctrine')->getManager();
        $this->connection   = $this->get('doctrine')->getConnection();
        $this->platform     = $this->connection->getDatabasePlatform();
        $this->indexName    = 'posts';
        $this->index        = $this->client->initIndex($this->getPrefix() . $this->indexName);
        $this->index->setSettings($this->getDefaultConfig())->wait();

        $contentsIndexName = 'contents';
        $contentsIndex     = $this->client->initIndex($this->getPrefix() . $contentsIndexName);
        $contentsIndex->setSettings($this->getDefaultConfig())->wait();

        $this->application = new Application(self::$kernel);
        $this->refreshDb($this->application);
    }

    public function tearDown()
    {
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
        $this->indexManager->delete(ContentAggregator::class);
    }

    public function testSearchClearUnknownIndex()
    {
        $unknownIndexName = 'test';

        $command       = $this->application->find('search:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            '--indices' => $unknownIndexName,
        ]);

        // Checks output and ensure it failed
        $output = $commandTester->getDisplay();
        $this->assertContains('No index named ' . $unknownIndexName, $output);
    }

    public function testSearchClear()
    {
        $this->om = $this->get('doctrine')->getManager();
        $result   = $this->indexManager->index($this->createPost(10), $this->om);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $response) {
                $response->wait();
            }
        }

        // Checks that post was created and indexed
        $searchPost = $this->indexManager->rawSearch('', Post::class);
        $this->assertCount(1, $searchPost['hits']);

        $command       = $this->application->find('search:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertContains('Cleared posts', $output);
    }

    public function testSearchImportAggregator()
    {
        $now = new \DateTime();
        $this->connection->insert($this->indexName, [
            'title'        => 'Test',
            'content'      => 'Test content',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);
        $this->connection->insert($this->indexName, [
            'title'        => 'Test2',
            'content'      => 'Test content2',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);
        $this->connection->insert($this->indexName, [
            'title'        => 'Test3',
            'content'      => 'Test content3',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $command       = $this->application->find('search:import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            '--indices' => 'contents',
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertContains('Done!', $output);

        $iteration      = 0;
        $expectedResult = 3;
        do {
            $searchPost = $this->indexManager->rawSearch('', ContentAggregator::class);
            sleep(1);
            $iteration++;
        } while (count($searchPost['hits']) !== $expectedResult || $iteration < 10);

        // Ensure posts were imported into contents index
        $searchPost = $this->indexManager->rawSearch('', ContentAggregator::class);
        $this->assertCount($expectedResult, $searchPost['hits']);
        // clearup table
        $this->connection->executeUpdate($this->platform->getTruncateTableSQL($this->indexName, true));
    }

    public function testSearchImport()
    {
        $now = new \DateTime();
        $this->connection->insert($this->indexName, [
            'title'        => 'Test',
            'content'      => 'Test content',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);
        $this->connection->insert($this->indexName, [
            'title'        => 'Test2',
            'content'      => 'Test content2',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);
        $this->connection->insert($this->indexName, [
            'title'        => 'Test3',
            'content'      => 'Test content3',
            'published_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $command       = $this->application->find('search:import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            '--indices' => $this->indexName,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertContains('Done!', $output);

        // Ensure posts were imported
        $iteration      = 0;
        $expectedResult = 3;
        do {
            $searchPost = $this->indexManager->rawSearch('', Post::class);
            sleep(1);
            $iteration++;
        } while (count($searchPost['hits']) !== $expectedResult || $iteration < 10);

        $this->assertCount($expectedResult, $searchPost['hits']);
        // clearup table
        $this->connection->executeUpdate($this->platform->getTruncateTableSQL($this->indexName, true));
    }

    public function testSearchSettingsBackupCommand()
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 51,
            'maxValuesPerFacet' => 99,
        ];
        $this->index->setSettings($settingsToUpdate)->wait();
        $command       = $this->application->find('search:settings:backup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            '--indices' => $this->indexName,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertContains('Saved settings', $output);

        $settingsFile = $this->getFileName($this->indexName, 'settings');

        $settingsFileContent = json_decode(file_get_contents($settingsFile), true);
        $this->assertContains($settingsToUpdate['hitsPerPage'], $settingsFileContent);
        $this->assertContains($settingsToUpdate['maxValuesPerFacet'], $settingsFileContent);
    }

    public function testSearchSettingsPushCommand()
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 50,
            'maxValuesPerFacet' => 100,
        ];
        $this->index->setSettings($settingsToUpdate)->wait();
        $settings     = $this->index->getSettings();
        $settingsFile = $this->getFileName($this->indexName, 'settings');

        $settingsFileContent = json_decode(file_get_contents($settingsFile), true);
        $this->assertNotEquals($settings['hitsPerPage'], $settingsFileContent['hitsPerPage']);
        $this->assertNotEquals($settings['maxValuesPerFacet'], $settingsFileContent['maxValuesPerFacet']);

        $command       = $this->application->find('search:settings:push');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'   => $command->getName(),
            '--indices' => $this->indexName,
        ]);

        // Checks output
        $output = $commandTester->getDisplay();
        $this->assertContains('Pushed settings', $output);

        // check if the settings were imported
        $iteration = 0;
        do {
            $newSettings = $this->index->getSettings();
            sleep(1);
            $iteration++;
        } while ($newSettings['hitsPerPage'] !== $settingsFileContent['hitsPerPage'] || $iteration < 10);

        $this->assertEquals($newSettings['hitsPerPage'], $settingsFileContent['hitsPerPage']);
        $this->assertEquals($newSettings['maxValuesPerFacet'], $settingsFileContent['maxValuesPerFacet']);
    }
}
