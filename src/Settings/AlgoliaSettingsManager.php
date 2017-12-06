<?php

namespace Algolia\SearchBundle\Settings;


use AlgoliaSearch\Client;
use Symfony\Component\Filesystem\Filesystem;

class AlgoliaSettingsManager implements SettingsManagerInterface
{
    protected $algolia;
    protected $config;

    public function __construct(Client $algolia, array $config)
    {
        $this->algolia = $algolia;
        $this->config = $config;
    }

    public function backup($settingsDir, array $params)
    {
        $indices = $this->getIndexNames($params['indices']);
        $fs = new Filesystem();
        $output = [];

        if (!$fs->exists($settingsDir)) {
            $fs->mkdir($settingsDir);
        }

        foreach ($indices as $indexName) {
            $index = $this->algolia->initIndex($indexName);
            $settings = $index->getSettings();
            $filename = $this->getFileName($settingsDir, $indexName, 'settings');

            $fs->dumpFile($filename, json_encode($settings, JSON_PRETTY_PRINT));

            $output[] = "Saved settings for <info>$indexName</info> in $filename";
        }

        return $output;
    }

    public function push($settingsDir, array $params)
    {
        $indices = $this->getIndexNames($params['indices']);
        $output = [];

        foreach ($indices as $indexName) {
            $filename = $this->getFileName($settingsDir, $indexName, 'settings');

            if (is_readable($filename)) {
                $index = $this->algolia->initIndex($indexName);
                $settings = json_decode(file_get_contents($filename));
                $index->setSettings($settings);

                $output[] = "Pushed settings for <info>$indexName</info>";
            }

        }

        return $output;
    }

    private function getIndexNames($indices)
    {
        if (empty($indices)) {
            $indices = array_keys($this->config['indices']);
        }

        foreach ($indices as &$name) {
            $name = $this->config['prefix'].$name;
        }

        return $indices;
    }

    private function getFileName($settingsDir, $indexName, $type)
    {
        return "$settingsDir/$indexName-$type.json";
    }
}
