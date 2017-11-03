<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableEntityInterface;

interface IndexingEngineInterface
{
    public function add(SearchableEntityInterface $entity);

    public function update(SearchableEntityInterface $searchableEntity);

    public function delete(SearchableEntityInterface $searchableEntity);

    public function clear($indexName);
}
