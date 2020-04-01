<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Doctrine\Common\Persistence\ObjectManager;

interface SearchService
{
    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSearchable($className);

    /**
     * @return array<int, string>
     */
    public function getSearchables();

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration();

    /**
     * Get the index name for the given `$className`.
     *
     * @param string $className
     *
     * @return string
     */
    public function searchableAs($className);

    /**
     * @param object|array<int, object>                      $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = []);

    /**
     * @param object|array<int, object>                      $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = []);

    /**
     * @param string                                         $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear($className, $requestOptions = []);

    /**
     * @param string                                         $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete($className, $requestOptions = []);

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<int, object>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search(ObjectManager $objectManager, $className, $query = '', $requestOptions = []);

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|bool|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch($className, $query = '', $requestOptions = []);

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($className, $query = '', $requestOptions = []);
}
