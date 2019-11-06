<?php

namespace Algolia\SearchBundle\Service;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\SearchBundle\Engine;
use Algolia\SearchBundle\Entity\Aggregator;
use Algolia\SearchBundle\Responses\SearchServiceResponse;
use Algolia\SearchBundle\SearchableEntity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class SearchService implements SearchServiceInterface
{
    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var array<string, array|int|string>
     */
    private $configuration;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var array<int, string>
     */
    private $searchableEntities;

    /**
     * @var array<int, string>
     */
    private $aggregators;

    /**
     * @var array<string, array>
     */
    private $entitiesAggregators;

    /**
     * @var array<string, string>
     */
    private $classToIndexMapping;

    /**
     * @var array<string, boolean>
     */
    private $classToSerializerGroupMapping;

    /**
     * @var array<string, string|null>
     */
    private $indexIfMapping;

    /**
     * @var mixed
     */
    private $normalizer;

    /**
     * @param mixed                           $normalizer
     * @param array<string, array|int|string> $configuration
     */
    public function __construct($normalizer, Engine $engine, array $configuration)
    {
        $this->normalizer       = $normalizer;
        $this->engine           = $engine;
        $this->configuration    = $configuration;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->setSearchableEntities();
        $this->setAggregatorsAndEntitiesAggregators();
        $this->setClassToIndexMapping();
        $this->setClassToSerializerGroupMapping();
        $this->setIndexIfMapping();
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSearchable($className)
    {
        if (is_object($className)) {
            $className = ClassUtils::getClass($className);
        }

        return in_array($className, $this->searchableEntities, true);
    }

    /**
     * @return array<int, string>
     */
    public function getSearchables()
    {
        return $this->searchableEntities;
    }

    /**
     * @return array<string, array|int|string>
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function searchableAs($className)
    {
        return $this->configuration['prefix'] . $this->classToIndexMapping[$className];
    }

    /**
     * @param object|array<int, object>                      $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function index(ObjectManager $objectManager, $searchables, $requestOptions = [])
    {
        $searchables = is_array($searchables) ? $searchables : [$searchables];
        $searchables = array_merge($searchables, $this->getAggregatorsFromEntities($objectManager, $searchables));

        $searchablesToBeIndexed = array_filter($searchables, function ($entity) {
            return $this->isSearchable($entity);
        });

        $searchablesToBeRemoved = [];
        foreach ($searchablesToBeIndexed as $key => $entity) {
            if (!$this->shouldBeIndexed($entity)) {
                unset($searchablesToBeIndexed[$key]);
                $searchablesToBeRemoved[] = $entity;
            }
        }

        if (count($searchablesToBeRemoved) > 0) {
            $this->remove($objectManager, $searchablesToBeRemoved);
        }

        return $this->makeSearchServiceResponseFrom($objectManager, $searchablesToBeIndexed, function ($chunk) use ($requestOptions) {
            return $this->engine->index($chunk, $requestOptions);
        });
    }

    /**
     * @param object|array<int, object>                      $searchables
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function remove(ObjectManager $objectManager, $searchables, $requestOptions = [])
    {
        $searchables = is_array($searchables) ? $searchables : [$searchables];
        $searchables = array_merge($searchables, $this->getAggregatorsFromEntities($objectManager, $searchables));

        $searchables = array_filter($searchables, function ($entity) {
            return $this->isSearchable($entity);
        });

        return $this->makeSearchServiceResponseFrom($objectManager, $searchables, function ($chunk) use ($requestOptions) {
            return $this->engine->remove($chunk, $requestOptions);
        });
    }

    /**
     * @param string                                         $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function clear($className, $requestOptions = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->clear($this->searchableAs($className), $requestOptions);
    }

    /**
     * @param string                                         $className
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    public function delete($className, $requestOptions = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->delete($this->searchableAs($className), $requestOptions);
    }

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<int, object>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function search(ObjectManager $objectManager, $className, $query = '', $requestOptions = [])
    {
        $this->assertIsSearchable($className);

        $ids = $this->engine->searchIds($query, $this->searchableAs($className), $requestOptions);

        $results = [];

        foreach ($ids as $objectID) {
            if (in_array($className, $this->aggregators, true)) {
                $entityClass = $className::getEntityClassFromObjectID($objectID);
                $id          = $className::getEntityIdFromObjectID($objectID);
            } else {
                $id          = $objectID;
                $entityClass = $className;
            }

            $repo   = $objectManager->getRepository($entityClass);
            $entity = $repo->findOneBy(['id' => $id]);

            if ($entity !== null) {
                $results[] = $entity;
            }
        }

        return $results;
    }

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return array<string, int|string|array>
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function rawSearch($className, $query = '', $requestOptions = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->search($query, $this->searchableAs($className), $requestOptions);
    }

    /**
     * @param string                                         $className
     * @param string                                         $query
     * @param array<string, int|string|array>|RequestOptions $requestOptions
     *
     * @return int
     *
     * @throws \Algolia\AlgoliaSearch\Exceptions\AlgoliaException
     */
    public function count($className, $query = '', $requestOptions = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->searchableAs($className), $requestOptions);
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    public function shouldBeIndexed($entity)
    {
        $className    = ClassUtils::getClass($entity);
        $propertyPath = $this->indexIfMapping[$className];

        if ($propertyPath !== null) {
            if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
                return (bool) $this->propertyAccessor->getValue($entity, $propertyPath);
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    private function canUseSerializerGroup($className)
    {
        return $this->classToSerializerGroupMapping[$className];
    }

    /**
     * @return void
     */
    private function setClassToIndexMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexName => $indexDetails) {
            $mapping[$indexDetails['class']] = $indexName;
        }

        $this->classToIndexMapping = $mapping;
    }

    /**
     * @return void
     */
    private function setSearchableEntities()
    {
        $searchable = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            $searchable[] = $index['class'];
        }

        $this->searchableEntities = array_unique($searchable);
    }

    /**
     * @return void
     */
    private function setAggregatorsAndEntitiesAggregators()
    {
        $this->entitiesAggregators = [];
        $this->aggregators         = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            if (is_subclass_of($index['class'], Aggregator::class)) {
                foreach ($index['class']::getEntities() as $entityClass) {
                    if (!isset($this->entitiesAggregators[$entityClass])) {
                        $this->entitiesAggregators[$entityClass] = [];
                    }

                    $this->entitiesAggregators[$entityClass][] = $index['class'];
                    $this->aggregators[]                       = $index['class'];
                }
            }
        }

        $this->aggregators = array_unique($this->aggregators);
    }

    /**
     * @param string $className
     *
     * @return void
     */
    private function assertIsSearchable($className)
    {
        if (!$this->isSearchable($className)) {
            throw new Exception('Class ' . $className . ' is not searchable.');
        }
    }

    /**
     * @return void
     */
    private function setClassToSerializerGroupMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['enable_serializer_groups'];
        }

        $this->classToSerializerGroupMapping = $mapping;
    }

    /**
     * @return void
     */
    private function setIndexIfMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['index_if'];
        }

        $this->indexIfMapping = $mapping;
    }

    /**
     * For each chunk performs the provided operation.
     *
     * @param array<int, object> $entities
     * @param callable           $operation
     *
     * @return \Algolia\AlgoliaSearch\Response\AbstractResponse
     */
    private function makeSearchServiceResponseFrom(ObjectManager $objectManager, array $entities, $operation)
    {
        $batch = [];
        foreach (array_chunk($entities, $this->configuration['batchSize']) as $chunk) {
            $searchableEntitiesChunk = [];
            foreach ($chunk as $entity) {
                $entityClassName = ClassUtils::getClass($entity);

                $searchableEntitiesChunk[] = new SearchableEntity(
                    $this->searchableAs($entityClassName),
                    $entity,
                    $objectManager->getClassMetadata($entityClassName),
                    $this->normalizer,
                    ['useSerializerGroup' => $this->canUseSerializerGroup($entityClassName)]
                );
            }

            $batch[] = $operation($searchableEntitiesChunk);
        }

        return new SearchServiceResponse($batch);
    }

    /**
     * Returns the aggregators instances of the provided entities.
     *
     * @param array<int, object> $entities
     *
     * @return array<int, object>
     */
    private function getAggregatorsFromEntities(ObjectManager $objectManager, array $entities)
    {
        $aggregators = [];

        foreach ($entities as $entity) {
            $entityClassName = ClassUtils::getClass($entity);
            if (array_key_exists($entityClassName, $this->entitiesAggregators)) {
                foreach ($this->entitiesAggregators[$entityClassName] as $aggregator) {
                    $aggregators[] = new $aggregator($entity, $objectManager->getClassMetadata($entityClassName)->getIdentifierValues($entity));
                }
            }
        }

        return $aggregators;
    }
}
