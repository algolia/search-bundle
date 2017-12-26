<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Settings\AlgoliaSettingsManager;
use Algolia\SearchBundle\Settings\SettingsManagerInterface;
use AlgoliaSearch\Client;

class SettingsTest extends BaseTest
{
    /** @var Client */
    private $client;

    /** @var SettingsManagerInterface */
    private $settingsManager;

    private $settingsDir = __DIR__.'/../cache/settings';

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->container->get('algolia.client');

        $this->settingsManager = new AlgoliaSettingsManager($this->client, [
            'prefix' => 'TRAVIS_sf_settings_',
            'settingsDirectory' => $this->settingsDir,
            'indices' => [
                'posts' => [
                    'class' => 'Algolia\SearchBundle\Entity\Post',
                    'enable_serializer_groups' => false,
                ],
            ],
        ]);
    }

    public function testBackup()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 51,
            'maxValuesPerFacet' => 99,
        ];
        $index = $this->client->initIndex('TRAVIS_sf_settings_posts');
        $task = $index->setSettings($settingsToUpdate);
        $index->waitTask($task['taskID']);

        $message = $this->settingsManager->backup(['indices' => ['posts']]);

        $this->assertContains('Saved settings for', $message[0]);
        $this->assertTrue(file_exists($this->settingsDir.'/TRAVIS_sf_settings_posts-settings.json'));

        $savedSettings = json_decode(file_get_contents(
            $this->settingsDir.'/TRAVIS_sf_settings_posts-settings.json'
        ), true);

        $this->assertArraySubset($settingsToUpdate, $savedSettings);
    }

    /**
     * @depends testBackup
     */
    public function testPush()
    {
        $settingsToUpdate = [
            'hitsPerPage' => 12,
            'maxValuesPerFacet' => 100,
        ];
        $index = $this->client->initIndex('TRAVIS_sf_settings_posts');
        $task = $index->setSettings($settingsToUpdate);
        $index->waitTask($task['taskID']);

        $message = $this->settingsManager->push(['indices' => ['posts']]);

        $this->assertContains('Pushed settings for', $message[0]);

        $savedSettings = json_decode(file_get_contents(
            $this->settingsDir.'/TRAVIS_sf_settings_posts-settings.json'
        ), true);

        for ($i=0;$i<5;$i++) {
            sleep(1);
            $settings = $index->getSettings();
            if (12 != $settings['hitsPerPage']) {
                $this->assertEquals($savedSettings, $settings);
            }
        }
    }
}
