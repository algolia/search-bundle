<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\SearchableEntityInterface;
use AlgoliaSearch\Client;

class AlgoliaEngine implements EngineInterface
{
    /** @var Client Client */
    protected $algolia;

    public function __construct(Client $algolia)
    {
        $this->algolia = $algolia;
    }

    public function add($searchableEntities)
    {
        return $this->update($searchableEntities);
    }

    public function update($searchableEntities)
    {
        if (
            $searchableEntities instanceof SearchableEntityInterface
            && !empty($record = $searchableEntities->getSearchableArray())
        ) {
            return [
                $searchableEntities->getIndexName() => $this->algolia
                    ->initIndex($searchableEntities->getIndexName())
                    ->addObject($record, $searchableEntities->getId())
            ];
        }

        return $this->batchUpdate($searchableEntities);
    }

    public function remove($searchableEntities)
    {
        if ($searchableEntities instanceof SearchableEntityInterface) {
            return [
                $searchableEntities->getIndexName() => $this->algolia
                    ->initIndex($searchableEntities->getIndexName())
                    ->deleteObject($searchableEntities->getId())
            ];
        }

        return $this->batchDelete($searchableEntities);
    }

    public function clear($indexName)
    {
        return [
            $indexName => $this->algolia->initIndex($indexName)->clearIndex()
        ];
    }

    public function delete($indexName)
    {
        return [
            $indexName => $this->algolia->deleteIndex($indexName)
        ];
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
            if (empty($entity->getSearchableArray())) {
                continue;
            }

            $indexName = $entity->getIndexName();

            if (! isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $entity->getSearchableArray() + [
                'objectID' => $entity->getId()
            ];
        }

        $result = [];
        foreach ($data as $indexName => $objects) {
            $result[$indexName] = $this->algolia
                ->initIndex($indexName)
                ->addObjects($objects);
        }

        return $result;
    }

    private function batchDelete($searchableEntities)
    {
        $data = [];
        foreach ($searchableEntities as $entity) {
            if (empty($entity->getSearchableArray())) {
                continue;
            }
            $indexName = $entity->getIndexName();

            if (! isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $entity->getId();
        }

        $result = [];
        foreach ($data as $indexName => $objects) {
            $result[$indexName] = $this->algolia
                ->initIndex($indexName)
                ->deleteObjects($objects);
        }

        return $result;
    }
}
