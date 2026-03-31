<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Settings\SettingsManager;
use Algolia\SearchBundle\TestApp\Entity\Post;

class SettingsTest extends BaseTest
{
    /** @var SearchClient */
    private $client;

    /** @var SettingsManager */
    private $settingsManager;
    private $configIndexes;
    private $indexName;

    public function setUp(): void
    {
        parent::setUp();

        $this->client          = $this->get('search.client');
        $this->settingsManager = $this->get('search.settings_manager');
        $this->configIndexes   = $this->get('search.service')->getConfiguration()['indices'];
        $this->indexName       = 'posts';
    }

    public function cleanUp(): void
    {
        $this->get('search.service')->delete(Post::class);
    }

    /**
     * @group internal
     */
    public function testBackup(): void
    {
        $this->rrmdir($this->get('search.service')->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage'       => 51,
            'maxValuesPerFacet' => 99,
        ];
        $fullIndexName = $this->getPrefix() . $this->indexName;
        $response = $this->client->setSettings($fullIndexName, $settingsToUpdate);
        $this->client->waitForTask($fullIndexName, $response['taskID']);

        $message = $this->settingsManager->backup(['indices' => [$this->indexName]]);

        self::assertStringContainsString('Saved settings for', $message[0]);
        self::assertFileExists($this->getFileName($this->indexName, 'settings'));

        $savedSettings = json_decode(file_get_contents(
            $this->getFileName($this->indexName, 'settings')
        ), true);

        self::assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
        self::assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
    }

    /**
     * @group internal
     */
    public function testBackupWithoutIndices(): void
    {
        $this->rrmdir($this->get('search.service')->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage'       => 51,
            'maxValuesPerFacet' => 99,
        ];

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $fullIndexName = $this->getPrefix() . $indexName;
            $response = $this->client->setSettings($fullIndexName, $settingsToUpdate);
            $this->client->waitForTask($fullIndexName, $response['taskID']);
        }

        $message = $this->settingsManager->backup(['indices' => []]);

        self::assertStringContainsString('Saved settings for', $message[0]);

        foreach ($this->configIndexes as $indexName => $configIndex) {
            self::assertFileExists($this->getFileName($this->indexName, 'settings'));

            $savedSettings = json_decode(file_get_contents(
                $this->getFileName($indexName, 'settings')
            ), true);

            self::assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
            self::assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
        }
    }

    /**
     * @depends testBackup
     */
    public function testPush(): void
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 12,
            'maxValuesPerFacet' => 100,
        ];
        $fullIndexName = $this->getPrefix() . $this->indexName;
        $response = $this->client->setSettings($fullIndexName, $settingsToUpdate);
        $this->client->waitForTask($fullIndexName, $response['taskID']);

        $message = $this->settingsManager->push(['indices' => [$this->indexName]]);

        self::assertStringContainsString('Pushed settings for', $message[0]);

        $savedSettings = json_decode(file_get_contents(
            $this->getFileName($this->indexName, 'settings')
        ), true);

        for ($i = 0; $i < 5; $i++) {
            sleep(1);
            $settings = $this->client->getSettings($fullIndexName);
            if (12 !== $settings['hitsPerPage']) {
                self::assertEquals($savedSettings['hitsPerPage'], $settings['hitsPerPage']);
                self::assertEquals($savedSettings['maxValuesPerFacet'], $settings['maxValuesPerFacet']);
            }
        }
    }

    /**
     * @depends testBackupWithoutIndices
     */
    public function testPushWithoutIndices(): void
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 12,
            'maxValuesPerFacet' => 100,
        ];

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $fullIndexName = $this->getPrefix() . $indexName;
            $response = $this->client->setSettings($fullIndexName, $settingsToUpdate);
            $this->client->waitForTask($fullIndexName, $response['taskID']);
        }

        $message = $this->settingsManager->push(['indices' => []]);

        self::assertStringContainsString('Pushed settings for', $message[0]);

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $savedSettings = json_decode(file_get_contents(
                $this->getFileName($indexName, 'settings')
            ), true);

            $fullIndexName = $this->getPrefix() . $indexName;
            for ($i = 0; $i < 5; $i++) {
                sleep(1);
                $settings = $this->client->getSettings($fullIndexName);
                if (12 !== $settings['hitsPerPage']) {
                    self::assertEquals($savedSettings['hitsPerPage'], $settings['hitsPerPage']);
                    self::assertEquals($savedSettings['maxValuesPerFacet'], $settings['maxValuesPerFacet']);
                }
            }
        }
        $this->cleanUp();
    }

    /**
     * @see https://www.php.net/rmdir
     */
    private function rrmdir(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
