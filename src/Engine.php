<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\SearchBundle\Responses\NullResponse;

/**
 * @internal
 */
final class Engine
{
    /** @var SearchClient */
    private $client;

    public function __construct(SearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return SearchClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Add new objects to an index.
     *
     * This method allows you to create records on your index by sending one or more objects.
     * Each object contains a set of attributes and values, which represents a full record on an index.
     *
     * @param array<int, SearchableEntity> $searchableEntities
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function index($searchableEntities, $requestOptions)
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

            $data[$indexName][] = array_merge(
                ['objectID' => $entity->getId()],
                $searchableArray
            );
        }

        $result = [];
        foreach ($data as $indexName => $objects) {
            $result[$indexName] = $this->client->saveObjects($indexName, $objects);
        }

        return $result;
    }

    /**
     * Remove objects from an index using their object ids.
     *
     * This method enables you to remove one or more objects from an index.
     *
     * @param array<int, SearchableEntity> $searchableEntities
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function remove($searchableEntities, $requestOptions)
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
            $result[$indexName] = $this->client->deleteObjects($indexName, $objects);
        }

        return $result;
    }

    /**
     * Clear the records of an index without affecting its settings.
     *
     * This method enables you to delete an index's contents (records) without
     * removing any settings, rules and synonyms.
     *
     * If you want to remove the entire index and not just its records, use the
     * delete method instead.
     *
     * @param string $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return mixed
     */
    public function clear($indexName, $requestOptions)
    {
        if ($this->client->indexExists($indexName)) {
            $response = $this->client->clearObjects($indexName);
            $this->client->waitForTask($indexName, $response['taskID']);

            return $response;
        }

        return new NullResponse();
    }

    /**
     * Delete an index and all its settings, including links to its replicas.
     *
     * This method not only removes an index from your application, it also
     * removes its metadata and configured settings (like searchable attributes or custom ranking).
     *
     * If the index has replicas, they will be preserved but will no longer be
     * linked to their primary index. Instead, they'll become independent indices.
     *
     * @param string $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return mixed
     */
    public function delete($indexName, $requestOptions)
    {
        if ($this->client->indexExists($indexName)) {
            $response = $this->client->deleteIndex($indexName);
            $this->client->waitForTask($indexName, $response['taskID']);

            return $response;
        }

        return new NullResponse();
    }

    /**
     * Method used for querying an index.
     *
     * @param string $query
     * @param string $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, int|string|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search($query, $indexName, $requestOptions)
    {
        $searchParams = array_merge(
            ['query' => $query],
            is_array($requestOptions) ? $requestOptions : []
        );

        return $this->client->searchSingleIndex($indexName, $searchParams);
    }

    /**
     * Search the index and returns the objectIDs.
     *
     * @param string $query
     * @param string $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<int, mixed>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function searchIds($query, $indexName, $requestOptions)
    {
        $result = $this->search($query, $indexName, $requestOptions);

        return array_map(function ($hit) {
            return $hit['objectID'];
        }, $result['hits']);
    }

    /**
     * Search the index and returns the number of results.
     *
     * @param string $query
     * @param string $indexName
     * @param array<string, int|string|array> $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($query, $indexName, $requestOptions)
    {
        $results = $this->search($query, $indexName, $requestOptions);

        return (int) $results['nbHits'];
    }
}
