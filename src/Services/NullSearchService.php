<?php

namespace Algolia\SearchBundle\Services;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\AlgoliaSearch\Response\NullResponse;
use Algolia\SearchBundle\SearchService;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * This class aims to be used in dev or testing environments. It may
 * be subject to breaking changes.
 */
class NullSearchService implements SearchService
{
    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSearchable($className)
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getSearchables()
    {
        return [];
    }

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration()
    {
        return [
            'batchSize' => 200,
        ];
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function searchableAs($className)
    {
        return $className;
    }

    /**
     * @param object|array<int, object>                           $searchables
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = [])
    {
        return new NullResponse();
    }

    /**
     * @param object|array<int, object>                           $searchables
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = [])
    {
        return new NullResponse();
    }

    /**
     * @param string                                              $className
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear($className, $requestOptions = [])
    {
        return new NullResponse();
    }

    /**
     * @param string                                              $className
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete($className, $requestOptions = [])
    {
        return new NullResponse();
    }

    /**
     * @param string                                              $className
     * @param string                                              $query
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return array<int, object>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search(ObjectManager $objectManager, $className, $query = '', $requestOptions = [])
    {
        return [
            new \stdClass(),
        ];
    }

    /**
     * @param string                                              $className
     * @param string                                              $query
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|bool|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch($className, $query = '', $requestOptions = [])
    {
        return [
            'hits'             => [],
            'nbHits'           => 0,
            'page'             => 0,
            'nbPages'          => 1,
            'hitsPerPage'      => 0,
            'exhaustiveNbHits' => true,
            'query'            => '',
            'params'           => '',
            'processingTimeMS' => 1,
        ];
    }

    /**
     * @param string                                              $className
     * @param string                                              $query
     * @param array<string, bool|int|string|array>|RequestOptions $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($className, $query = '', $requestOptions = [])
    {
        return 0;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function shouldBeIndexed($entity)
    {
        return false;
    }
}
