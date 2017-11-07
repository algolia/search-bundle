<?php

namespace Algolia\SearchBundle\Searchable;


interface IndexConfigurationInterface
{
    public function isSearchable($className);

    public function getIndexName($className);

    public function getPrefix();

    public function getConfiguration();
}
