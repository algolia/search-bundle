<?php

namespace Algolia\SearchBundle\Responses;

use Algolia\AlgoliaSearch\Response\AbstractResponse;

final class SearchServiceResponse extends AbstractResponse implements \Iterator
{
    /**
     * @var int Stores the current traversal position. An iterator may have a
     *          lot of other fields for storing iteration state, especially when it is
     *          supposed to work with a particular kind of collection.
     */
    private $position = 0;

    /**
     * @param array<int, array<string, AbstractResponse>> $apiResponse
     */
    public function __construct($apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * @param array<string, int|string|array>|mixed $requestOptions
     *
     * @return void
     */
    public function wait($requestOptions = [])
    {
        foreach ($this->apiResponse as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                /* @var $apiResponse AbstractResponse */
                $apiResponse->wait($requestOptions);
            }
        }
    }

    /**
     * @return void
     */
    public function rewind() : void
    {
        $this->position = 0;
    }

    /**
     * @return array<string, AbstractResponse>
     */
    public function current() : array
    {
        return $this->apiResponse[$this->key()];
    }

    /**
     * @return int
     */
    public function key() : int
    {
        return $this->position;
    }

    /**
     * @return void
     */
    public function next() : void
    {
        $this->position++;
    }

    /**
     * @return bool
     */
    public function valid() : bool
    {
        return array_key_exists($this->position, $this->apiResponse);
    }
}
