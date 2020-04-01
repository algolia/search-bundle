<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\AlgoliaSearch\SearchClient;
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

    public function setUp()
    {
        parent::setUp();

        $this->client          = $this->get('search.client');
        $this->settingsManager = $this->get('search.settings_manager');
        $this->configIndexes   = $this->get('search.service')->getConfiguration()['indices'];
        $this->indexName       = 'posts';
    }

    public function cleanUp()
    {
        $this->get('search.service')->delete(Post::class)->wait();
    }

    public function testBackup()
    {
        $this->rrmdir($this->get('search.service')->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage'       => 51,
            'maxValuesPerFacet' => 99,
        ];
        $index = $this->client->initIndex($this->getPrefix() . $this->indexName);
        $index->setSettings($settingsToUpdate)->wait();

        $message = $this->settingsManager->backup(['indices' => [$this->indexName]]);

        $this->assertContains('Saved settings for', $message[0]);
        $this->assertFileExists($this->getFileName($this->indexName, 'settings'));

        $savedSettings = json_decode(file_get_contents(
            $this->getFileName($this->indexName, 'settings')
        ), true);

        $this->assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
        $this->assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
    }

    public function testBackupWithoutIndices()
    {
        $this->rrmdir($this->get('search.service')->getConfiguration()['settingsDirectory']);
        $settingsToUpdate = [
            'hitsPerPage'       => 51,
            'maxValuesPerFacet' => 99,
        ];

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $index = $this->client->initIndex($this->getPrefix() . $indexName);
            $index->setSettings($settingsToUpdate)->wait();
        }

        $message = $this->settingsManager->backup(['indices' => []]);

        $this->assertContains('Saved settings for', $message[0]);

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $this->assertFileExists($this->getFileName($this->indexName, 'settings'));

            $savedSettings = json_decode(file_get_contents(
                $this->getFileName($indexName, 'settings')
            ), true);

            $this->assertEquals($settingsToUpdate['hitsPerPage'], $savedSettings['hitsPerPage']);
            $this->assertEquals($settingsToUpdate['maxValuesPerFacet'], $savedSettings['maxValuesPerFacet']);
        }
    }

    /**
     * @depends testBackup
     */
    public function testPush()
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 12,
            'maxValuesPerFacet' => 100,
        ];
        $index = $this->client->initIndex($this->getPrefix() . $this->indexName);
        $index->setSettings($settingsToUpdate)->wait();

        $message = $this->settingsManager->push(['indices' => [$this->indexName]]);

        $this->assertContains('Pushed settings for', $message[0]);

        $savedSettings = json_decode(file_get_contents(
            $this->getFileName($this->indexName, 'settings')
        ), true);

        for ($i = 0; $i < 5; $i++) {
            sleep(1);
            $settings = $index->getSettings();
            if (12 != $settings['hitsPerPage']) {
                $this->assertEquals($savedSettings, $settings);
            }
        }
    }

    /**
     * @depends testBackupWithoutIndices
     */
    public function testPushWithoutIndices()
    {
        $settingsToUpdate = [
            'hitsPerPage'       => 12,
            'maxValuesPerFacet' => 100,
        ];

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $index = $this->client->initIndex($this->getPrefix() . $indexName);
            $index->setSettings($settingsToUpdate)->wait();
        }

        $message = $this->settingsManager->push(['indices' => []]);

        $this->assertContains('Pushed settings for', $message[0]);

        foreach ($this->configIndexes as $indexName => $configIndex) {
            $savedSettings = json_decode(file_get_contents(
                $this->getFileName($indexName, 'settings')
            ), true);

            for ($i = 0; $i < 5; $i++) {
                sleep(1);
                $settings = $index->getSettings();
                if (12 != $settings['hitsPerPage']) {
                    $this->assertEquals($savedSettings, $settings);
                }
            }
        }
        $this->cleanUp();
    }

    /**
     * @see https://www.php.net/rmdir
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
