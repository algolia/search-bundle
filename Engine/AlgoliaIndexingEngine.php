<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableInterface;
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

    public function add(SearchableInterface $searchableEntity)
    {
        $this->update($searchableEntity);
    }

    public function update(SearchableInterface $searchableEntity)
    {
        $record = $searchableEntity->getSearchableArray();

        $this->getIndexer($searchableEntity->getIndexName())
            ->addObject($record, $searchableEntity->getId());
    }

    public function delete(SearchableInterface $searchableEntity)
    {
        $this->getIndexer($searchableEntity->getIndexName())
            ->deleteObject($searchableEntity->getId());
    }

    protected function getIndexer($indexName)
    {
        if (! isset($this->indexers[$indexName])) {
            $this->indexers[$indexName] = $this->algolia->initIndex($indexName);
        }

        return $this->indexers[$indexName];

    }
}
