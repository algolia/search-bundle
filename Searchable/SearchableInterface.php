<?php

namespace Algolia\SearchBundle\Searchable;


interface SearchableInterface
{
    public function getIndexName(); // searchableAs

    public function getRecord(); // toSearchableArray

    public function getObjectID();
}
