<?php

namespace Algolia\SearchBundle\Settings;

use Algolia\AlgoliaSearch\SearchClient;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class SettingsManager
{
    /**
     * @var SearchClient
     */
    private $algolia;

    /**
     * @var array<string, array|int|string>
     */
    private $config;

    /**
     * @param array<string, array|int|string> $config
     */
    public function __construct(SearchClient $algolia, array $config)
    {
        $this->algolia = $algolia;
        $this->config  = $config;
    }

    /**
     * @param array<string, array|int|string> $params
     *
     * @return array<int, string>
     */
    public function backup(array $params)
    {
        $indices = $this->getIndexNames($params['indices']);
        $fs      = new Filesystem();
        $output  = [];

        if (!$fs->exists($this->config['settingsDirectory'])) {
            $fs->mkdir($this->config['settingsDirectory']);
        }

        foreach ($indices as $indexName) {
            $this->backupIndice($indexName, $fs, $output);
        }

        return $output;
    }

    private function backupIndice($indexName, $fs, &$output): void
    {
        $index    = $this->algolia->initIndex($indexName);
        $settings = $index->getSettings();

        // Handle replicas
        if (array_key_exists('replicas', $settings)) {
            foreach($settings['replicas'] as &$replica) {
                // Backup replica settings
                $this->backupIndice($replica, $fs, $output);

                $replica = $this->removePrefixFromIndexName($replica);
            }
        }

        if (array_key_exists('primary', $settings)) {
            $settings['primary'] = $this->removePrefixFromIndexName($settings['primary']);
        }

        $filename = $this->getFileName($indexName, 'settings');

        $fs->dumpFile($filename, json_encode($settings, JSON_PRETTY_PRINT));

        $output[] = "Saved settings for <info>$indexName</info> in $filename";
    }

    /**
     * @param array<string, array|int|string> $params
     *
     * @return array<int, string>
     */
    public function push(array $params)
    {
        $indices = $this->getIndexNames($params['indices']);
        $output  = [];

        foreach ($indices as $indexName) {
            $filename = $this->getFileName($indexName, 'settings');

            if (is_readable($filename)) {
                $index    = $this->algolia->initIndex($indexName);
                $settings = json_decode(file_get_contents($filename), true);
                $index->setSettings($settings);

                $output[] = "Pushed settings for <info>$indexName</info>";
            }
        }

        return $output;
    }

    /**
     * @param array<int, string> $indices
     *
     * @return array<int, string>
     */
    private function getIndexNames($indices)
    {
        if (count($indices) === 0) {
            $indices = array_keys($this->config['indices']);
        }

        foreach ($indices as &$name) {
            $name = $this->config['prefix'] . $name;
        }

        return $indices;
    }

    /**
     * @param string $indexName
     * @param string $type
     *
     * @return string
     */
    private function getFileName($indexName, $type)
    {
        $indexName = $this->removePrefixFromIndexName($indexName);

        return sprintf('%s/%s-%s.json', $this->config['settingsDirectory'], $indexName, $type);
    }

    /**
     * @param string $indexName
     *
     * @return string|string[]|null
     */
    private function removePrefixFromIndexName($indexName)
    {
        return preg_replace('/^' . preg_quote($this->config['prefix'], '/') . '/', '', $indexName);
    }
}
