<?php

namespace Algolia\SearchBundle\Searchable;


interface SearchableEntityInterface
{
    public function getIndexName();

    public function getSearchableArray();

    public function getId();
}
