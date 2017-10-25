<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableInterface;

interface IndexingEngineInterface
{
    public function add(SearchableInterface $entity);

    public function update(SearchableInterface $searchableEntity);

    public function delete(SearchableInterface $searchableEntity);
}
