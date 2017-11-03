<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableEntityInterface;
use AlgoliaSearch\Client;
use AlgoliaSearch\Index;

class AlgoliaIndexingEngine implements IndexingEngineInterface
{
    /** @var Index[] */
    protected $indexers;

    /** @var Client Client */
    protected $algolia;

    public function __construct(Client $algolia)
    {
        $this->algolia = $algolia;
        $this->indexers = [];
    }

    public function add(SearchableEntityInterface $searchableEntity)
    {
        $this->update($searchableEntity);
    }

    public function update(SearchableEntityInterface $searchableEntity)
    {
        $record = $searchableEntity->getSearchableArray();

        $this->getIndexer($searchableEntity->getIndexName())
            ->addObject($record, $searchableEntity->getId());
    }

    public function delete(SearchableEntityInterface $searchableEntity)
    {
        $this->getIndexer($searchableEntity->getIndexName())
            ->deleteObject($searchableEntity->getId());
    }

    protected function getIndexer($indexName)
    {
        return $this->algolia->initIndex($indexName);
    }
}
