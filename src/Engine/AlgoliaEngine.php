<?php

namespace Algolia\SearchBundle\Engine;

use Algolia\SearchBundle\SearchableEntityInterface;
use Algolia\AlgoliaSearch\Client;

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
        $batch = $this->doUpdate($searchableEntities);

        return $this->formatIndexingResponse($batch);
    }

    public function remove($searchableEntities)
    {
        $batch = $this->doRemove($searchableEntities);

        return $this->formatIndexingResponse($batch);
    }

    public function clear($indexName)
    {
        try {
            $this->doClear($indexName);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($indexName)
    {
        try {
            $this->doDelete($indexName);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function search($query, $indexName, $page = 1, $nbResults = null, array $parameters = [])
    {
        $parameters += [
            'page' => $page - 1,
        ];
        if ($nbResults) {
            $parameters['hitsPerPage'] = $nbResults;
        }

        return $this->algolia->index($indexName)->search($query, $parameters);
    }

    public function searchIds($query, $indexName, $page = 1, $nbResults = null, array $parameters = [])
    {
        $result = $this->search($query, $indexName, $page, $nbResults, $parameters);

        return array_column($result['hits'], 'objectID');
    }

    public function count($query, $indexName)
    {
        $results = $this->algolia->index($indexName)->search($query);

        return (int) $results['nbHits'];
    }

    protected function doUpdate($searchableEntities)
    {
        if ($searchableEntities instanceof SearchableEntityInterface) {
            $searchableEntities = [$searchableEntities];
        }

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
                ->index($indexName)
                ->saveObjects($objects);
        }

        return $result;
    }

    protected function doRemove($searchableEntities)
    {
        if ($searchableEntities instanceof SearchableEntityInterface) {
            $searchableEntities = [$searchableEntities];
        }

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
        foreach ($data as $indexName => $objectsIds) {
            $result[$indexName] = $this->algolia
                ->index($indexName)
                ->deleteObjects($objectsIds);
        }

        return $result;
    }

    protected function doClear($indexName)
    {
        return [
            $indexName => $this->algolia->clearIndex($indexName)
        ];
    }

    protected function doDelete($indexName)
    {
        return [
            $indexName => $this->algolia->deleteIndex($indexName)
        ];
    }

    protected function formatIndexingResponse($batch)
    {
        $response = [];
        foreach ($batch as $indexName => $res) {
            $response[$indexName] = count($res['objectIDs']);
        }

        return $response;
    }
}
