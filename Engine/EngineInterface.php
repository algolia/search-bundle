<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableEntityInterface;

interface EngineInterface
{
    public function add(SearchableEntityInterface $entity);

    public function update(SearchableEntityInterface $searchableEntity);

    public function delete(SearchableEntityInterface $searchableEntity);

    public function clear($indexName);

    public function search($query, $indexName, $page = 0, $nbResults = 20, array $parameters = []);

    public function searchIds($query, $indexName, $page = 0, $nbResults = 20, array $parameters = []);

    public function count($query, $indexName);
}
