<?php

namespace Algolia\SearchBundle\Responses;

use Algolia\AlgoliaSearch\Api\SearchClient;

/**
 * @internal
 */
final class EngineResponse
{
    /** @var SearchClient */
    private $client;

    /** @var string */
    private $indexName;

    /** @var int */
    private $taskID;

    public function __construct(SearchClient $client, string $indexName, int $taskID)
    {
        $this->client    = $client;
        $this->indexName = $indexName;
        $this->taskID    = $taskID;
    }

    public function wait()
    {
        $this->client->waitForTask($this->indexName, $this->taskID);
    }
}
