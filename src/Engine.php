<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Response\BatchIndexingResponse;
use Algolia\AlgoliaSearch\Response\NullResponse;
use Algolia\AlgoliaSearch\SearchClient;

/**
 * @internal
 */
final class Engine
{
    /** @var SearchClient */
    private $client;

    /**
     * @param SearchClient $client
     */
    public function __construct(SearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * Save entities to Algolia.
     *
     * @param array<int, SearchableEntity>    $searchableEntities
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, BatchIndexingResponse>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function save($searchableEntities, $requestOptions = [])
    {
        if ($searchableEntities instanceof SearchableEntity) {
            $searchableEntities = [$searchableEntities];
        }

        $data = [];
        foreach ($searchableEntities as $entity) {
            $searchableArray = $entity->getSearchableArray();
            if ($searchableArray === null || count($searchableArray) === 0) {
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
            if (!array_key_exists('autoGenerateObjectIDIfNotExist', $requestOptions)) {
                $requestOptions['autoGenerateObjectIDIfNotExist'] = true;
            }

            $result[$indexName] = $this->client
                ->initIndex($indexName)
                ->saveObjects($objects, $requestOptions);
        }

        return $result;
    }

    /**
     * Remove entities from Algolia.
     *
     * @param array<int, SearchableEntity>    $searchableEntities
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, BatchIndexingResponse>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function remove($searchableEntities, $requestOptions = [])
    {
        if ($searchableEntities instanceof SearchableEntity) {
            $searchableEntities = [$searchableEntities];
        }

        $data = [];
        foreach ($searchableEntities as $entity) {
            $searchableArray = $entity->getSearchableArray();
            if ($searchableArray === null || count($searchableArray) === 0) {
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
            $result[$indexName] = $this->client
                ->initIndex($indexName)
                ->deleteObjects($objects, $requestOptions);
        }

        return $result;
    }

    /**
     * Clear all objects from the index.
     *
     * @param string $indexName
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear($indexName)
    {
        $index = $this->client->initIndex($indexName);

        if ($index->exists()) {
            return $index->clearObjects();
        }

        return new NullResponse();
    }

    /**
     * Delete the index.
     *
     * @param string $indexName
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete($indexName)
    {
        $index = $this->client->initIndex($indexName);

        if ($index->exists()) {
            return $index->delete();
        }

        return new NullResponse();
    }

    /**
     * Search the index.
     *
     * @param string                          $query
     * @param string                          $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, int|string|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search($query, $indexName, $requestOptions = [])
    {
        return $this->client->initIndex($indexName)->search($query, $requestOptions);
    }

    /**
     * Search the index and returns the objectIDs.
     *
     * @param string                          $query
     * @param string                          $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, int|string|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function searchIds($query, $indexName, $requestOptions = [])
    {
        $result = $this->search($query, $indexName, $requestOptions);

        return array_column($result['hits'], 'objectID');
    }

    /**
     * Search the index and returns the number of results.
     *
     * @param string                          $query
     * @param string                          $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($query, $indexName, $requestOptions = [])
    {
        $results = $this->client->initIndex($indexName)->search($query, $requestOptions);

        return (int) $results['nbHits'];
    }
}
