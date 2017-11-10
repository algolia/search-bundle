<?php

namespace Algolia\SearchableBundle;


interface SearchableEntityInterface
{
    public function getIndexName();

    public function getSearchableArray();

    public function getId();
}
