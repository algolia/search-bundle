<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Entity\Aggregator;
use Algolia\SearchBundle\Engine\EngineInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;

class IndexManager implements IndexManagerInterface
{
    protected $engine;
    protected $configuration;
    protected $useSerializerGroups;

    private $propertyAccessor;
    private $searchableEntities;
    private $aggregators;
    private $entitiesAggregators;
    private $classToIndexMapping;
    private $classToSerializerGroupMapping;
    private $indexIfMapping;
    private $normalizer;

    public function __construct($normalizer, EngineInterface $engine, array $configuration)
    {
        $this->normalizer          = $normalizer;
        $this->engine              = $engine;
        $this->configuration       = $configuration;
        $this->propertyAccessor    = PropertyAccess::createPropertyAccessor();

        $this->setSearchableEntities();
        $this->setAggregatorsAndEntitiesAggregators();
        $this->setClassToIndexMapping();
        $this->setClassToSerializerGroupMapping();
        $this->setIndexIfMapping();
    }

    public function isSearchable($className)
    {
        if (is_object($className)) {
            $className = ClassUtils::getClass($className);
        }

        return in_array($className, $this->searchableEntities);
    }

    public function getSearchableEntities()
    {
        return $this->searchableEntities;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function index($entities, ObjectManager $objectManager)
    {
        $entities = is_array($entities) ? $entities : [$entities];
        $entities = array_merge($entities, $this->getAggregatorsFromEntities($objectManager, $entities));

        $entitiesToBeIndexed = array_filter($entities, function ($entity) {
            return $this->isSearchable($entity);
        });

        $entitiesToBeRemoved = [];
        foreach ($entitiesToBeIndexed as $key => $entity) {
            if (! $this->shouldBeIndexed($entity)) {
                unset($entitiesToBeIndexed[$key]);
                $entitiesToBeRemoved[] = $entity;
            }
        }

        if (! empty($entitiesToBeRemoved)) {
            $this->remove($entitiesToBeRemoved, $objectManager);
        }

        return $this->forEachChunk($objectManager, $entitiesToBeIndexed, function ($chunk) {
            return $this->engine->update($chunk);
        });
    }

    public function remove($entities, ObjectManager $objectManager)
    {
        $entities = is_array($entities) ? $entities : [$entities];
        $entities = array_merge($entities, $this->getAggregatorsFromEntities($objectManager, $entities));

        $entities = array_filter($entities, function ($entity) {
            return $this->isSearchable($entity);
        });

        return $this->forEachChunk($objectManager, $entities, function ($chunk) {
            return $this->engine->remove($chunk);
        });
    }

    public function clear($className)
    {
        $this->assertIsSearchable($className);

        return $this->engine->clear($this->getFullIndexName($className));
    }

    public function delete($className)
    {
        $this->assertIsSearchable($className);

        return $this->engine->delete($this->getFullIndexName($className));
    }

    public function search($query, $className, ObjectManager $objectManager, $page = 1, $nbResults = null, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        if (!is_int($nbResults)) {
            $nbResults = $this->configuration['nbResults'];
        }

        $ids = $this->engine->searchIds($query, $this->getFullIndexName($className), $page, $nbResults, $parameters);

        $results = [];

        foreach ($ids as $objectID) {
            if (in_array($className, $this->aggregators, true)) {
                $entityClass = $className::getEntityClassFromObjectID($objectID);
                $id = $className::getEntityIdFromObjectID($objectID);
            } else {
                $id = $objectID;
                $entityClass = $className;
            }

            $repo = $objectManager->getRepository($entityClass);
            $entity = $repo->findOneBy(['id' => $id]);

            if ($entity !== null) {
                $results[] = $entity;
            }
        }

        return $results;
    }

    public function rawSearch($query, $className, $page = 1, $nbResults = null, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        if (!is_int($nbResults)) {
            $nbResults = $this->configuration['nbResults'];
        }

        return $this->engine->search($query, $this->getFullIndexName($className), $page,  $nbResults, $parameters);
    }

    public function count($query, $className, array $parameters = [])
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->getFullIndexName($className), $parameters);
    }

    public function shouldBeIndexed($entity)
    {
        $className = ClassUtils::getClass($entity);

        if ($propertyPath = $this->indexIfMapping[$className]) {
            if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
                return (bool) $this->propertyAccessor->getValue($entity, $propertyPath);
            }

            return false;
        }

        return true;
    }

    private function canUseSerializerGroup($className)
    {
        return $this->classToSerializerGroupMapping[$className];
    }

    private function setClassToIndexMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexName => $indexDetails) {
            $mapping[$indexDetails['class']] = $indexName;
        }

        $this->classToIndexMapping = $mapping;
    }

    private function setSearchableEntities()
    {
        $searchable = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            $searchable[] = $index['class'];
        }

        $this->searchableEntities = array_unique($searchable);
    }

    private function setAggregatorsAndEntitiesAggregators()
    {
        $this->entitiesAggregators = [];
        $this->aggregators = [];

        foreach ($this->configuration['indices'] as $name => $index) {
            if (is_subclass_of($index['class'], Aggregator::class)) {
                foreach ($index['class']::getEntities() as $entityClass) {

                    if (! isset($this->entitiesAggregators[$entityClass])) {
                        $this->entitiesAggregators[$entityClass] = [];
                    }

                    $this->entitiesAggregators[$entityClass][] = $index['class'];
                    $this->aggregators[] = $index['class'];
                }
            }
        }

        $this->aggregators = array_unique($this->aggregators);
    }

    public function getFullIndexName($className)
    {
        return $this->configuration['prefix'].$this->classToIndexMapping[$className];
    }

    private function assertIsSearchable($className)
    {
        if (!$this->isSearchable($className)) {
            throw new Exception('Class '.$className.' is not searchable.');
        }
    }

    private function setClassToSerializerGroupMapping()
    {
        $mapping = [];
        foreach ($this->configuration['indices'] as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['enable_serializer_groups'];
        }

        $this->classToSerializerGroupMapping = $mapping;
    }

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
     * @param  \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param  array $entities
     * @param  callable $operation
     * @return array
     */
    private function forEachChunk(ObjectManager $objectManager, array $entities, $operation)
    {
        $batch = [];
        foreach (array_chunk($entities, $this->configuration['batchSize']) as $chunk) {
            $searchableEntitiesChunk = [];
            foreach ($chunk as $entity) {
                $entityClassName = ClassUtils::getClass($entity);

                $searchableEntitiesChunk[] = new SearchableEntity(
                    $this->getFullIndexName($entityClassName),
                    $entity,
                    $objectManager->getClassMetadata($entityClassName),
                    $this->normalizer,
                    ['useSerializerGroup' => $this->canUseSerializerGroup($entityClassName)]
                );
            }

            $batch[] = $operation($searchableEntitiesChunk);
        }

        return $this->formatBatchResponse($batch);
    }

    /**
     * Returns the aggregators instances of the provided entities.
     *
     * @param  \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param  object[] $entities
     * @return array
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

    private function formatBatchResponse(array $batch)
    {
        $formattedResponse = [];
        foreach ($batch as $response) {
            if (!is_array($response)) {
                continue;
            }

            foreach ($response as $fullIndexName => $count) {
                $indexName = $this->removePrefixFromIndexName($fullIndexName);

                if (!isset($formattedResponse[$indexName])) {
                    $formattedResponse[$indexName] = 0;
                }

                $formattedResponse[$indexName] += $count;
            }
        }

        return $formattedResponse;
    }

    private function removePrefixFromIndexName($indexName)
    {
        return preg_replace('/^'.preg_quote($this->configuration['prefix'], '/').'/', '', $indexName);
    }
}
