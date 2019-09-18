<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\SearchClient;

class AlgoliaEngine
{
    /** @var SearchClient */
    protected $algolia;

    public function __construct(SearchClient $algolia)
    {
        $this->algolia = $algolia;
    }

    public function add($searchableEntities, $requestOptions = [])
    {
        return $this->update($searchableEntities, $requestOptions);
    }

    public function update($searchableEntities, $requestOptions = [])
    {
        $batch = $this->doUpdate($searchableEntities, $requestOptions);

        return $this->formatIndexingResponse($batch);
    }

    public function remove($searchableEntities, $requestOptions = [])
    {
        $batch = $this->doRemove($searchableEntities, $requestOptions);

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

    public function search($query, $indexName, $requestOptions = [])
    {
        return $this->algolia->initIndex($indexName)->search($query, $requestOptions);
    }

    public function searchIds($query, $indexName, $requestOptions = [])
    {
        $result = $this->search($query, $indexName, $requestOptions);

        return array_column($result['hits'], 'objectID');
    }

    public function count($query, $indexName, $requestOptions = [])
    {
        $results = $this->algolia->initIndex($indexName)->search($query, $requestOptions);

        return (int) $results['nbHits'];
    }

    protected function doUpdate($searchableEntities, $requestOptions = [])
    {
        if ($searchableEntities instanceof SearchableEntity) {
            $searchableEntities = [$searchableEntities];
        }

        $data = [];
        foreach ($searchableEntities as $entity) {
            $searchableArray = $entity->getSearchableArray();
            if (empty($searchableArray)) {
                continue;
            }

            $indexName = $entity->getIndexName();

            if (!isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $searchableArray + [
                'objectID' => $entity->getId(),
            ];
        }

        $result = [];
        foreach ($data as $indexName => $objects) {
            $result[$indexName] = $this->algolia
                ->initIndex($indexName)
                ->saveObjects($objects, $requestOptions);
        }

        return $result;
    }

    protected function doRemove($searchableEntities, $requestOptions = [])
    {
        if ($searchableEntities instanceof SearchableEntity) {
            $searchableEntities = [$searchableEntities];
        }

        $data = [];
        foreach ($searchableEntities as $entity) {
            if (empty($entity->getSearchableArray())) {
                continue;
            }
            $indexName = $entity->getIndexName();

            if (!isset($data[$indexName])) {
                $data[$indexName] = [];
            }

            $data[$indexName][] = $entity->getId();
        }

        $result = [];
        foreach ($data as $indexName => $objects) {
            $result[$indexName] = $this->algolia
                ->initIndex($indexName)
                ->deleteObjects($objects, $requestOptions);
        }

        return $result;
    }

    protected function doClear($indexName)
    {
        return [
            $indexName => $this->algolia->initIndex($indexName)->clearObjects(),
        ];
    }

    protected function doDelete($indexName)
    {
        return [
            $indexName => $this->algolia->initIndex($indexName)->delete(),
        ];
    }

    protected function formatIndexingResponse($batch)
    {
        $response = [];
        foreach ($batch as $indexName => $res) {
            $response[$indexName] = count($res->current()['objectIDs']);
        }

        return $response;
    }
}
