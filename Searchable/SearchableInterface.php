<?php

namespace Algolia\SearchBundle\Searchable;


interface SearchableInterface
{
    public function getIndexName(); // searchableAs

    public function getSearchableArray(); // toSearchableArray

    public function getId();
}
