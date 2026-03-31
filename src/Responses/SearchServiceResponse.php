<?php

namespace Algolia\SearchBundle\Responses;

use Algolia\AlgoliaSearch\Api\SearchClient;

final class SearchServiceResponse implements \Iterator
{
    /**
     * @var int Stores the current traversal position. An iterator may have a
     *          lot of other fields for storing iteration state, especially when it is
     *          supposed to work with a particular kind of collection.
     */
    private $position = 0;

    /** @var SearchClient */
    private $client;

    /** @var array */
    private $apiResponse;

    /**
     * @param array<int, array<string, array>> $apiResponse
     */
    public function __construct(SearchClient $client, $apiResponse)
    {
        $this->client = $client;
        $this->apiResponse = $apiResponse;
    }

    /**
     * @return void
     */
    public function wait()
    {
        foreach ($this->apiResponse as $chunk) {
            foreach ($chunk as $indexName => $batchResponses) {
                foreach ($batchResponses as $batchResponse) {
                    $this->client->waitForTask($indexName, $batchResponse['taskID']);
                }
            }
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return array<string, array>
     */
    public function current(): array
    {
        return $this->apiResponse[$this->key()];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->apiResponse);
    }
}
