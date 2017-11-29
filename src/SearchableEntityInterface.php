<?php

namespace Algolia\SearchBundle;


interface SearchableEntityInterface
{
    public function getIndexName();

    public function getSearchableArray();

    public function getId();
}
