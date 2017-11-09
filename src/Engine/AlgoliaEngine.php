<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\Searchable\SearchableEntityInterface;
use AlgoliaSearch\Client;
use AlgoliaSearch\Version;

class AlgoliaEngine implements EngineInterface
{
    /** @var Client Client */
    protected $algolia;

    public function __construct(Client $algolia)
    {
        Version::addSuffixUserAgentSegment('Symfony Searchable', '0.1.0');

        $this->algolia = $algolia;
    }

    public function add($searchableEntities)
    {
        $this->update($searchableEntities);
    }

    public function update($searchableEntities)
    {
        if ($searchableEntities instanceof SearchableEntityInterface) {
            $this->algolia
                ->initIndex($searchableEntities->getIndexName())
                ->addObject(
                    $searchableEntities->getSearchableArray(), $searchableEntities->getId()
                );
        } else {
            $this->batchUpdate($searchableEntities);
        }
    }

    public function delete($searchableEntities)
    {
        if ($searchableEntities instanceof SearchableEntityInterface) {
            $this->algolia
                ->initIndex($searchableEntities->getIndexName())
                ->deleteObject($searchableEntities->getId());
        } else {
            $this->batchDelete($searchableEntities);
        }
    }

    public function clear($indexName)
    {
        $this->algolia->initIndex($indexName)->clearIndex();
    }

    public function search($query, $indexName, $page = 1, $nbResults = null, array $parameters = [])
    {
        $params = array_merge($parameters, [
            'hitsPerPage' => $nbResults,
            'page' => $page - 1,
        ]);

        return $this->algolia->initIndex($indexName)->search($query, $params);
    }

    public function searchIds($query, $indexName, $page = 1, $nbResults = null, array $parameters = [])
    {
        $result = $this->search($query, $indexName, $page, $nbResults, $parameters);

        return array_column($result['hits'], 'objectID');
    }

    public function count($query, $indexName)
    {
        $results = $this->algolia->initIndex($indexName)->search($query);

        return (int) $results['nbHits'];
    }

    private function batchUpdate($searchableEntities)
    {
        $data = [];
        foreach ($searchableEntities as $entity) {
            $indexName = $entity->getIndexName();

            if (! isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $entity->getSearchableArray() + [
                'objectID' => $entity->getId()
            ];
        }

        foreach ($data as $indexName => $objects) {
            $this->algolia
                ->initIndex($indexName)
                ->addObjects($objects);
        }
    }

    private function batchDelete($searchableEntities)
    {
        $data = [];
        foreach ($searchableEntities as $entity) {
            $indexName = $entity->getIndexName();

            if (! isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $entity->getId();
        }

        foreach ($data as $indexName => $objects) {
            $this->algolia
                ->initIndex($indexName)
                ->deleteObjects($objects);
        }
    }
}
