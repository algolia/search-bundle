<?php

namespace Algolia\SearchBundle\Searchable;


interface SearchableInterface
{
    public function getIndexName();

    public function getSearchableArray();

    public function getId();
}
