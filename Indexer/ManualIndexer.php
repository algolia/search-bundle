<?php

namespace Algolia\AlgoliaSearchBundle\Indexer;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Algolia\AlgoliaSearchBundle\Exception\NotAnAlgoliaEntity;

class ManualIndexer
{
    /** @var Indexer */
    private $indexer;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(Indexer $indexer, ObjectManager $entityManager)
    {
        $this->indexer = $indexer;
        $this->objectManager = $entityManager;

        $this->indexer->setObjectManager($entityManager);
    }

    /**
     * Indexes the entities provided.
     *
     * If $indexName is specified, it will override the name of the index
     * that the engine would normally pick.
     */
    private function doIndex(array $entities, $indexName = null)
    {
        foreach ($entities as $entity) {
            if (!$this->indexer->discoverEntity($entity, $this->objectManager)) {
                throw new NotAnAlgoliaEntity(
                    'Tried to index entity of class `'.get_class($entity).'`, which is not recognized as an entity to index.'
                );
            }

            if ($indexName) {
                $this->indexer->scheduleEntityCreation([
                    'indexName' => $indexName,
                    'entity' => $entity
                ]);
            } else {
                $this->indexer->scheduleEntityCreation($entity);
            }
        }

        $this->indexer->processScheduledIndexChanges();
    }

    private function doUnIndex($entities)
    {
        foreach ($entities as $entity) {
            if (!$this->indexer->discoverEntity($entity, $this->objectManager)) {
                throw new NotAnAlgoliaEntity(
                    'Tried to unIndex entity of class `'.get_class($entity).'`, which is not recognized as an entity to index.'
                );
            }

            $this->indexer->scheduleEntityDeletion($entity);
        }

        $this->indexer->processScheduledIndexChanges();
    }

    private function batchArray(array $entities, $batchSize, $callback)
    {
        array_map($callback, array_chunk($entities, $batchSize));

        return count($entities);
    }

    /**
     * @param string $entityName
     * @param mixed $query
     * @param int $batchSize
     * @param callable $callback
     * @param bool $clearObjectManager
     * @return int
     */
    private function batchQuery($entityName, $query, $batchSize, $callback, $clearObjectManager = false)
    {
        if ($this->objectManager instanceof DocumentManager) {
            $queryObject = new Query\ODM($this->objectManager, $entityName);
            return $queryObject->batchQuery($batchSize, $callback, $query, $clearObjectManager);
        } elseif ($this->objectManager instanceof EntityManager) {
            $queryObject = new Query\ORM($this->objectManager, $entityName);
            return $queryObject->batchQuery($batchSize, $callback, $query, $clearObjectManager);
        } else {
            throw new \LogicException('Cannot manually index with object manager of class ' . get_class($this->objectManager));
        }
    }

    /**
     * Manually index the provided entities.
     *
     * Please note that the entities need to have a primary key, hence be already saved in the DB.
     *
     * When passing an entity name as $entities, if no query is provided in the $options array, then all entities are indexed.
     * Otherwise, the query provided is used to fetch the entities. This allows the use of
     * any kind of DQL conditions to determine what to re-index (objects created after a certain date, with a specific status...).
     * When providing a query, it is the programmers responsibility to make sure it will return entities of $entityName class.
     *
     * @param  mixed $entities Either a single entity, an array of entities, or an entity name.
     * @param  array $options  An array of options that MAY contain `batchSize` (int), `query` (a Doctrine Query)
     * @return int   The number of entities processed
     */
    public function index($entities, array $options = array())
    {
        $defaults = [
            'batchSize' => 1000,
            'query' => null,
            'indexName' => null, // default is to let the engine guess
            'clearEntityManager' => false,
        ];

        $options = array_merge($defaults, $options);

        return $this->doBatch(
            $entities,
            $options,
            function ($batch) use ($options) {
                $this->doIndex($batch, $options['indexName']);
            }
        );
    }

    /**
     * Manually un-index the provided entities.
     *
     * Please note that the entities need to have a primary key, so manual un-indexing must be done BEFORE deleting
     * the objects from the local DB.
     *
     * When passing an entity name as $entities, if no query is provided in the $options array, then all entities are un-indexed.
     * Otherwise, the query provided is used to fetch the entities. This allows the use of
     * any kind of DQL conditions to determine what to re-index (objects created after a certain date, with a specific status...).
     * When providing a query, it is the programmers responsibility to make sure it will return entities of $entityName class.
     *
     * @param  mixed $entities Either a single entity, an array of entities, or an entity name.
     * @param  array $options  An array of options that MAY contain `batchSize` (int), `query` (a Doctrine Query)
     * @return int   The number of entities processed
     */
    public function unIndex($entities, array $options = array())
    {
        $defaults = [
            'batchSize' => 1000,
            'query' => null,
            'clearEntityManager' => false,
        ];

        $options = array_merge($defaults, $options);
        return $this->doBatch(
            $entities,
            $options,
            function ($batch) {
                $this->doUnIndex($batch);
            }
        );
    }

    public function clear($entityName)
    {
        $className =  $this->objectManager->getRepository($entityName)->getClassName();

        if (!$this->indexer->discoverEntity($className, $this->objectManager)) {
            throw new NotAnAlgoliaEntity(
                'Tried to index entity of class `'.get_class($className).'`, which is not recognized as an entity to index.'
            );
        }

        $targetIndexName = $this->indexer->getAlgoliaIndexName($className);

        $this->indexer->getIndex($targetIndexName)->clearIndex();
    }

    /**
     * Re-index entities from a collection.
     *
     * If no query is provided in the $options array, then all entities are re-indexed.
     * Otherwise, the query provided is used to fetch the entities. This allows the use of
     * any kind of DQL conditions to determine what to re-index (objects created after a certain date, with a specific status...).
     * When providing a query, it is the programmers responsibility to make sure it will return entities of $entityName class.
     *
     * If the `safe` option is provided, re-indexing will be done on a brand new index (with the same settings as the target one),
     * which will be moved atomically to the target index when indexing is complete.
     *
     * @param  string $entityName The name of the entities to reindex, may be either a class name or a Doctrine class alias
     * @param  array  $options    An array of options, that may contain `batchSize` (int), `safe` (bool), `query` (Doctrine\ORM\Query)
     * @return int    The number of processed entities.
     */
    public function reIndex($entityName, array $options = array())
    {
        $defaults = [
            'safe' => true,
            'batchSize' => 1000,
            'query' => null,
            'clearEntityManager' => false,
        ];

        $options = array_merge($defaults, $options);

        $className =  $this->objectManager->getRepository($entityName)->getClassName();

        if (!$this->indexer->discoverEntity($className, $this->objectManager)) {
            throw new NotAnAlgoliaEntity(
                'Tried to index entity of class `'.$className.'`, which is not recognized as an entity to index.'
            );
        }

        $targetIndexName = $this->indexer->getAlgoliaIndexName($className);

        $indexTo = $targetIndexName;

        if ($options['safe']) {
            $indexTo .= '__TEMPORARY__INDEX__'.microtime(true);
            try {
                // Copy settings from master index to temporary index
                $masterSettings = $this->indexer->getIndex($targetIndexName)->getSettings();
                $this->indexer->getIndex($indexTo)->setSettings($masterSettings);
            } catch (\AlgoliaSearch\AlgoliaException $e) {
                // It's OK if the master index did not exist! No settings to set.
                if ($e->getMessage() !== 'Index does not exist') {
                    throw $e;
                }
            }
        }

        $nProcessed = $this->index($entityName, [
            'batchSize' => $options['batchSize'],
            'query' => $options['query'],
            'indexName' => $indexTo,
            'clearEntityManager' => $options['clearEntityManager'],
        ]);

        if ($options['safe']) {
            $this->indexer->algoliaTask(
                $targetIndexName,
                $this->indexer->getClient()->moveIndex($indexTo, $targetIndexName)
            );
        }

        return $nProcessed;
    }

    /**
     * @param array|object|string $entities
     * @param array $options
     * @param $callback
     * @return int
     *
     * @throws \LogicException
     */
    private function doBatch($entities, array $options, $callback)
    {
        if (is_object($entities)) {
            $callback([$entities]);
            return 1;
        } elseif (is_array($entities)) {
            return $this->batchArray($entities, $options['batchSize'], $callback);
        } elseif (! is_string($entities)) {
            return 0;
        }

        return $this->batchQuery($entities, $options['query'], $options['batchSize'], $callback, $options['clearEntityManager']);
    }
}
