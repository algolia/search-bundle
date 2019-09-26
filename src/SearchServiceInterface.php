<?php

namespace Algolia\SearchBundle;

use Algolia\AlgoliaSearch\Response\AbstractResponse;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @internal
 *
 * This class should be used for testing purposes only.
 * It exists so the service can be mocked.
 */
interface SearchServiceInterface
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
    public function getSearchableEntities();

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration();

    /**
     * @param string $className
     *
     * @return string
     */
    public function getFullIndexName($className);

    /**
     * @param object|array<int, object>       $entities
     * @param ObjectManager                   $objectManager
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<int, array<string, AbstractResponse>>
     */
    public function index($entities, ObjectManager $objectManager, $requestOptions = []);

    /**
     * @param object|array<int, object>       $entities
     * @param ObjectManager                   $objectManager
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<int, array<string, AbstractResponse>>
     */
    public function remove($entities, ObjectManager $objectManager, $requestOptions = []);

    /**
     * @param string $className
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear($className);

    /**
     * @param string $className
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete($className);

    /**
     * @param string                          $query
     * @param string                          $className
     * @param ObjectManager                   $objectManager
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<int, object>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search($query, $className, ObjectManager $objectManager, $requestOptions = []);

    /**
     * @param string                          $query
     * @param string                          $className
     * @param array<string, int|string|array> $requestOptions
     *
     * @return array<string, int|string|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch($query, $className, $requestOptions = []);

    /**
     * @param string                          $query
     * @param string                          $className
     * @param array<string, int|string|array> $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($query, $className, $requestOptions = []);
}
