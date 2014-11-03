<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping;

class Index
{
    private $algoliaName;
    private $perEnvironment = true;
    private $autoIndex = true;
    private $identifierFieldNames;

    // The names of the settings only we care about (client side)
    private static $internalSettingsProps = [
        'algoliaName',
        'perEnvironment',
        'autoIndex'
    ];

    // The names of the settings that Algolia servers care about
    public static $algoliaSettingsProps = [
        'minWordSizefor1Typo',
        'minWordSizefor2Typos',
        'hitsPerPage',
        'attributesToIndex',
        'attributesToRetrieve',
        'unretrievableAttributes',
        'optionalWords',
        'attributesForFaceting',
        'attributesToSnippet',
        'attributesToHighlight',
        'attributeForDistinct',
        'ranking',
        'customRanking',
        'separatorsToIndex',
        'removeWordsIfNoResults',
        'queryType',
        'highlightPreTag',
        'highlightPostTag',
        'slaves'
    ];

    public function getAlgoliaName()
    {
        return $this->algoliaName;
    }

    public function setAlgoliaNameFromClass($class)
    {
        $this->algoliaName = substr($class, strrpos($class, '\\') + 1);

        return $this;
    }

    public function updateSettingsFromArray(array $settings)
    {
        foreach (self::$internalSettingsProps as $field) {
            if (array_key_exists($field, $settings)) {
                $this->$field = $settings[$field];
            }
        }

        foreach (self::$algoliaSettingsProps as $field) {
            if (array_key_exists($field, $settings)) {
                $this->$field = $settings[$field];
            }
        }

        return $this;
    }

    public function getAutoIndex()
    {
        return $this->autoIndex;
    }

    public function getPerEnvironment()
    {
        return $this->perEnvironment;
    }

    /**
	 * Returns the index settings in a format
	 * compatible with that expected by https://github.com/algolia/algoliasearch-client-php
	 */
    public function getAlgoliaSettings()
    {
        $settings = [];

        foreach (self::$algoliaSettingsProps as $field) {
            if (isset($this->$field)) {
                $settings[$field] = $this->$field;
            }
        }

        return $settings;
    }
}
