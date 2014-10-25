<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Indexer;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Exception\UnknownEntity;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Exception\MissingGetter;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Exception\NotCallable;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Exception\NoPrimaryKey;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Exception\NotAnAlgoliaEntity;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Loader\AnnotationLoader;

class Indexer
{
    private static $annotationReader;

    /**
     * Holds index settings for entities we're interested in.
     * 
     * Keys are fully qualified class names (i.e. with namespace),
     * values are as returned by the MetaDataLoader.
     * 
     * Please see the documentation for MetaDataLoaderInterface::getMetaData
     * for more details.
     */
    private static $indexSettings = array();

    /**
     * The arrays below hold the entities we will sync with
     * Algolia on postFlush.
     */
    // holds naked entities
    private $entitiesScheduledForCreation = array();
    // holds arrays like ['entity' => $entity, 'changeSet' => $changeSet]
    private $entitiesScheduledForUpdate = array();
    // holds arrays like ['objectID' => 'aStringID', 'index' => 'anIndexName']
    private $entitiesScheduledForDeletion = array();

    // Stores the current environment, this is injected by Symfo
    // at service instanciation.
    private $environment;

    /**
     * The algolia application_id and api_key.
     * Also injected for us by symfony from the config.
     */
    private $apiSettings = array();

    private $client;

    // Used to wait for sync, keys are index names
    private $latestAlgoliaTaskID = array();

    // Cache index objects from the php client lib
    private $indices = array();

    /**
     * @internal
     * Used by the depency injection mechanism of Symfony
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setApiSettings(array $apiSettings)
    {
        $this->apiSettings = $apiSettings;

        return $this;
    }

    /**
     * Right now this only returns a MetaDataAnnotationLoader,
     * but this abstraction is provided to enable other loaders later.
     * A loader just has to implement the Algolia\AlgoliaSearchSymfonyDoctrineBundle\MetaData\MetaDataLoaderInterface
     * @return see MetaDataLoaderInterface
     */
    public function getMetaDataLoader()
    {
        return new AnnotationLoader();
    }

    /**
     * This function does 2 things at once for efficiency:
     * - return a simple boolean telling us whether or not there might be
     *   indexing work to do with this entity
     * - extract, and store for later, the index settings for the entity
     *   if we're interested in indexing it
     * @param  $entity
     * @return bool
     * @internal
     */
    public function interestedIn($entity, $em)
    {
        $class = get_class($entity);

        // check if we already saw this type of entity
        // to avoid some expensive work
        if (!array_key_exists($class, self::$indexSettings)) {
            self::$indexSettings[$class] = $this->getMetaDataLoader()->getMetaData($entity, $em);
        }

        return false !== self::$indexSettings[$class];
    }

    /**
     * Tells us whether we need to autoindex this entity.
     * @internal
     */
    public function autoIndex($entity, $em)
    {
        if (!$this->interestedIn($entity, $em)) {
            return false;
        } else {
            return self::$indexSettings[get_class($entity)]->getIndex()->getAutoIndex();
        }
    }

    /**
     * Determines whether the IndexIf conditions allow indexing this entity.
     * If a changeSet is specified, returns array($shouldBeIndexedNow, $wasIndexedBefore),
     * Otherwise just returns whether it should be indexed now.
     * @internal
     */
    private function shouldIndex($entity, array $changeSet = null)
    {
        $class = get_class($entity);

        $needsIndexing = true;
        $wasIndexed = true;

        foreach (self::$indexSettings[$class]->getIndexIfs() as $if) {
            if (null === $changeSet) {
                if (!$if->evaluate($entity)) {
                    return false;
                }
            } else {
                list ($newValue, $oldValue) = $if->diff($entity, $changeSet);
                $needsIndexing = $needsIndexing && $newValue;
                $wasIndexed = $wasIndexed && $oldValue;
            }
        }
        
        return null === $changeSet ? true : array($needsIndexing, $wasIndexed);
    }

    /**
     * Determines whether the IndexIf conditions allowed the entity
     * to be indexed when the entity had the internal values provided
     * in the $originalData array.
     * @internal
     */
    private function shouldHaveBeenIndexed($entity, array $originalData)
    {
        foreach (self::$indexSettings[get_class($entity)]->getIndexIfs() as $if) {
            if (!$if->evaluateWith($entity, $originalData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @internal
     */
    public function scheduleEntityCreation($entity, $checkShouldIndex = true)
    {
        if ($checkShouldIndex && !$this->shouldIndex($entity)) {
            return;
        }

        // We store the whole entity, because its ID will not be available until post-flush
        $this->entitiesScheduledForCreation[] = $entity;
    }

    /**
     * @internal
     */
    public function scheduleEntityUpdate($entity, array $changeSet)
    {
        list($shouldIndex, $wasIndexed) = $this->shouldIndex($entity, $changeSet);

        if ($shouldIndex) {
            if ($wasIndexed) {
                // We need to store the changeSet now, as it will not be available post-flush
                $this->entitiesScheduledForUpdate[] = array('entity' => $entity, 'changeSet' => $changeSet);
            } else {
                $this->scheduleEntityCreation($entity, ($checkShouldIndex = false));
            }
        } elseif ($wasIndexed) {
            // If the entity was indexed, and now should not be, then remove it.
            $this->scheduleEntityDeletion($entity, null);
        }
    }

    /**
     * @internal
     */
    public function scheduleEntityDeletion($entity, array $originalData = null)
    {
        // Don't unindex entities that were not already indexed!
        if (null !== $originalData && !$this->shouldHaveBeenIndexed($entity, $originalData)) {
            return;
        }

        // We need to get the primary key now, because post-flush it will be gone from the entity
        list($primaryKey, $unusedOldPrimaryKey) = $this->getPrimaryKeyForAlgolia($entity);
        $this->entitiesScheduledForDeletion[] = array(
            'objectID' => $primaryKey,
            'index' => $this->getAlgoliaIndexName($entity)
        );
    }

    /**
     * OOP? Encapsulation? No thanks! :)
     * http://php.net/manual/en/closure.bind.php
     */
    private function extractPropertyValue($entity, $field)
    {
        $privateGetter = \Closure::bind(function ($field) {
            return $this->$field;
        }, $entity, $entity);

        return $privateGetter($field);
    }

    /**
     * @internal
     */
    public function getPrimaryKeyForAlgolia($entity, array $changeSet = null)
    {
        $class = get_class($entity);
        if (!isset(self::$indexSettings[$class])) {
            throw new UnknownEntity("Entity `$class` is not known to Algolia. This is likely an implementation bug.");
        }

        $changed = false;

        $oldPrimaryKeyValues = array();
        $newPrimaryKeyValues = array();

        foreach (self::$indexSettings[$class]->getIdentifierFieldNames() as $fieldName) {
            $old = null;
            $new = null;

            if (is_array($changeSet) && array_key_exists($fieldName, $changeSet)) {
                $old = $changeSet[$fieldName][0];
                $new = $changeSet[$fieldName][1];
                $changed = true;
            } else {
                $old = $new = $this->extractPropertyValue($entity, $fieldName);
            }

            if (!$new) {
                throw new NoPrimaryKey(
                    "An entity without a valid primary key was found during synchronization with Algolia."
                );
            }

            $oldPrimaryKeyValues[] = $old;
            $newPrimaryKeyValues[] = $new;
        }

        $primaryKey = implode(',', $newPrimaryKeyValues);
        $oldPrimaryKey = $changed ? implode(',', $oldPrimaryKeyValues) : null;

        return array($primaryKey, $oldPrimaryKey);
    }

    /**
     * @internal
     */
    public function getFieldsForAlgolia($entity, array $changeSet = null)
    {
        $class = get_class($entity);
        if (!isset(self::$indexSettings[$class])) {
            throw new UnknownEntity("Entity of class `$class` is not known to Algolia. This is likely an implementation bug.");
        }

        $fields = array();

        // Get fields coming from properties
        foreach (self::$indexSettings[$class]->getProperties() as $prop) {

            // When performing an update, ignore unchanged properties
            if (is_array($changeSet) && !array_key_exists($prop->getName(), $changeSet)) {
                continue;
            }

            $fields[$prop->getAlgoliaName()] = $this->extractPropertyValue($entity, $prop->getName());
        }

        // Get fields coming from methods
        foreach (self::$indexSettings[$class]->getMethods() as $meth) {
            // When performing an update, ignore unchanged properties
            if (is_array($changeSet)) {
                list($newValue, $oldValue) = $meth->diff($entity, $changeSet);
                if ($newValue === $oldValue) {
                    continue;
                }
            }

            $fields[$meth->getAlgoliaName()] = $meth->evaluate($entity);
        }

        return $fields;
    }

    /**
     * @internal
     */
    public function getAlgoliaIndexName($entity)
    {
        $class = get_class($entity);
        if (!isset(self::$indexSettings[$class])) {
            throw new UnknownEntity("Entity $class is not known to Algolia. This is likely an implementation bug.");
        }

        $index = self::$indexSettings[$class]->getIndex();
        $indexName = $index->getAlgoliaName();

        if ($index->getPerEnvironment() && $this->environment) {
            $indexName .= '_'.$this->environment;
        }

        return $indexName;
    }
    
    /**
     * @internal
     */
    public function processScheduledIndexChanges()
    {
        $creations = array();
        $updates = array();
        $deletions = array();

        foreach ($this->entitiesScheduledForCreation as $entity) {
            
            $index = $this->getAlgoliaIndexName($entity);
            
            list($primaryKey, $unusedOldPrimaryKey) = $this->getPrimaryKeyForAlgolia($entity);
            $fields = $this->getFieldsForAlgolia($entity);

            if (!empty($fields)) {
                if (!isset($creations[$index])) {
                    $creations[$index] = array();
                }
                $fields['objectID'] = $primaryKey;
                $creations[$index][] = $fields;
            }
        }

        foreach ($this->entitiesScheduledForUpdate as $data) {
            
            $index = $this->getAlgoliaIndexName($data['entity']);
            
            list($primaryKey, $oldPrimaryKey) = $this->getPrimaryKeyForAlgolia($data['entity'], $data['changeSet']);

            // The very unlikely case where a primary key changed
            if (null !== $oldPrimaryKey) {
                if (!isset($deletions[$index])) {
                    $deletions[$index] = array();
                }
                $deletions[$index][] = $oldPrimaryKey;

                $fields = $this->getFieldsForAlgolia($data['entity'], null);
                $fields['objectID'] = $primaryKey;

                if (!isset($creations[$index])) {
                    $creations[$index] = array();
                }
                $creations[$index][] = $fields;
            } else {
                $fields = $this->getFieldsForAlgolia($data['entity'], $data['changeSet']);

                if (!empty($fields)) {
                    if (!isset($updates[$index])) {
                        $updates[$index] = array();
                    }
                    $fields['objectID'] = $primaryKey;
                    $updates[$index][] = $fields;
                }
            }
        }

        foreach ($this->entitiesScheduledForDeletion as $data) {
            
            $index = $data['index'];
            
            if (!isset($deletions[$index])) {
                $deletions[$index] = array();
            }
            $deletions[$index][] = $data['objectID'];
        }

        $this->performBatchCreations($creations);
        $this->performBatchUpdates($updates);
        $this->performBatchDeletions($deletions);

        $this->removeScheduledIndexChanges();

        return array(
            'creations' => $creations,
            'updates' => $updates,
            'deletions' => $deletions
        );
    }

    private function algoliaTask($indexName, $res)
    {
        if (!empty($res['taskID'])) {
            if (!isset($this->latestAlgoliaTaskID[$indexName]) || $res['taskID'] > $this->latestAlgoliaTaskID[$indexName]) {
                $this->latestAlgoliaTaskID[$indexName] = $res['taskID'];
            }
        }

        return $res;
    }

    /**
     * This function does creations or updates in the HTTP sense
     * of the REST specification, i.e. sends full resources,
     * be them new or updated.
     * @internal
     */
    protected function performBatchCreations(array $creations)
    {
        foreach ($creations as $indexName => $objects) {
            $this->algoliaTask(
                $indexName,
                $this->getIndex($indexName)->saveObjects($objects)
            );
        }
    }

    /**
     * This function does updates in the sense of PATCHes,
     * i.e. it handles deltas.
     * @internal
     */
    protected function performBatchUpdates(array $updates)
    {
        foreach ($updates as $indexName => $objects) {
            $this->algoliaTask(
                $indexName,
                $this->getIndex($indexName)->partialUpdateObjects($objects)
            );
        }
    }

    /**
     * This performs deletions, no trick here.
     * @internal
     */
    protected function performBatchDeletions(array $deletions)
    {
        foreach ($deletions as $indexName => $objectIDs) {
            $this->algoliaTask(
                $indexName,
                $this->getIndex($indexName)->deleteObjects($objectIDs)
            );
        }
    }

    /**
     * @internal
     */
    public function removeScheduledIndexChanges()
    {
        $this->entitiesScheduledForCreation = array();
        $this->entitiesScheduledForUpdate = array();
        $this->entitiesScheduledForDeletion = array();

        return $this;
    }

    protected function newInstance()
    {
        // We make a new indexer for manual indexing
        // because if we use this one, we risk
        // forgetting changes coming from autoIndexed entities
        $indexer = new static();
        $indexer->setEnvironment($this->environment);
        $indexer->setApiSettings($this->apiSettings);

        return $indexer;
    }

    /**
     * Manually index entities.
     *
     * Entities must be either an array of entities or a single entity.
     * Entities are expected to be already saved in the local DB and to
     * have a primary key.
     * A NoPrimaryKey exception will be raised if that's not the case.
     */
    public function index($em, $entities)
    {
        if (!is_array($entities)) {
            $entities = array($entities);
        }

        $indexer = $this->newInstance();

        foreach ($entities as $entity) {

            if (!$indexer->interestedIn($entity, $em)) {
                throw new NotAnAlgoliaEntity(
                    'Tried to index entity of class `'.get_class($entity).'`, which is not recognized as an entity to index.'
                );
            }

            $indexer->scheduleEntityCreation($entity);
        }

        return $indexer->processScheduledIndexChanges();
    }

    /**
     * Manually unIndex entities.
     *
     * Entities must be either an array of entities or a single entity.
     * Entities are expected to be already saved in the local DB and to
     * have a primary key.
     * A NoPrimaryKey exception will be raised if that's not the case.
     *
     * Please note that you need to unIndex entities before removing them
     * from the local DB, otherwise their primary keys will be lost.
     */
    public function unIndex($em, $entities)
    {
        if (!is_array($entities)) {
            $entities = array($entities);
        }

        $indexer = $this->newInstance();

        foreach ($entities as $entity) {

            if (!$this->interestedIn($entity, $em)) {
                throw new NotAnAlgoliaEntity(
                    'Tried to unIndex entity of class `'.get_class($entity).'`, which is not recognized as an entity to index.'
                );
            }

            $indexer->scheduleEntityDeletion($entity);
        }

        return $indexer->processScheduledIndexChanges();
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \AlgoliaSearch\Client(
                $this->apiSettings['application_id'],
                $this->apiSettings['api_key']
            );
        }

        return $this->client;
    }

    public function getIndex($indexName)
    {
        if (!isset($this->indices[$indexName])) {
            $this->indices[$indexName] = $this->getClient()->initIndex($indexName);
        }

        return $this->indices[$indexName];
    }

    public function search($indexName, $queryString, array $options = array())
    {
        $defaultOptions = [
            'perEnvironment' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $client = $this->getClient();

        if ($options['perEnvironment']) {
            $indexName .= '_' . $this->environment;
        }

        // does initIndex cost something? If so, TODO: put $index in cache.
        $index = $client->initIndex($indexName);

        return $index->search($queryString);
    }

    public function deleteIndex($indexName, array $options = array())
    {
        $defaultOptions = [
            'perEnvironment' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $client = $this->getClient();

        if ($options['perEnvironment']) {
            $indexName .= '_' . $this->environment;
        }

        $this->getClient()->deleteIndex($indexName);

        return $this;
    }

    public function waitForAlgoliaTasks()
    {
        foreach ($this->latestAlgoliaTaskID as $indexName => $taskID) {
            $this->getIndex($indexName)->waitTask($taskID);
            unset($this->latestAlgoliaTaskID[$indexName]);
        }

        return $this;
    }
}
