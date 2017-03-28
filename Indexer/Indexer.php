<?php

namespace Algolia\AlgoliaSearchBundle\Indexer;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Algolia\AlgoliaSearchBundle\Exception\UnknownEntity;
use Algolia\AlgoliaSearchBundle\Exception\NoPrimaryKey;
use Algolia\AlgoliaSearchBundle\Exception\NotAnAlgoliaEntity;
use Algolia\AlgoliaSearchBundle\Mapping\Loader\AnnotationLoader;
use Algolia\AlgoliaSearchBundle\SearchResult\SearchResult;

class Indexer
{
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

    // holds either naked entities or arrays of the form [
    // 'entity' => $someEntity,
    // 'indexName' => 'index name to override where the entity should normally go'
    // ]

    // holds arrays like ['entity' => $entity, 'changeSet' => $changeSet]
    protected $entitiesScheduledForCreation = array();

    // holds arrays like ['entity' => $entity, 'changeSet' => $changeSet]
    protected $entitiesScheduledForUpdate = array();

    // holds arrays like ['objectID' => 'aStringID', 'index' => 'anIndexName']
    protected $entitiesScheduledForDeletion = array();

    // Stores the current environment, this is injected by Symfo
    // at service instanciation.
    private $environment;

    // Stores the current index name prefix, this is injected by Symfo
    // at service instanciation.
    private $indexNamePrefix;

    /**
     * The algolia application_id and api_key.
     * Also injected for us by symfony from the config.
     */
    private $apiSettings = array();

    private $client;

    // Used to wait for sync, keys are index names
    private $latestAlgoliaTaskID = array();

    // Cache index objects from the php client lib
    protected $indices = array();

    private $objectManager;

    public function __construct()
    {
        \AlgoliaSearch\Version::$custom_value = ' Symfony';
    }

    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function getIndexSettings()
    {
        return self::$indexSettings;
    }

    /**
     * @internal
     * Used by the depency injection mechanism of Symfony
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @param mixed $indexNamePrefix
     */
    public function setIndexNamePrefix($indexNamePrefix)
    {
        $this->indexNamePrefix = $indexNamePrefix;
    }

    public function setApiSettings($application_id, $api_key, $connection_timeout = null)
    {
        $this->apiSettings = [
            'application_id' => $application_id,
            'api_key' => $api_key,
            'connection_timeout' => $connection_timeout,
        ];

        return $this;
    }

    /**
     * Right now this only returns a MetaDataAnnotationLoader,
     * but this abstraction is provided to enable other loaders later.
     * A loader just has to implement the Algolia\AlgoliaSearchBundle\MetaData\MetaDataLoaderInterface
     * @return see MetaDataLoaderInterface
     * @internal
     */
    public function getMetaDataLoader()
    {
        return new AnnotationLoader();
    }

    private function getClass($entity)
    {
        $class = get_class($entity);
        $class = ClassUtils::getRealClass($class);

        return $class;
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
    public function discoverEntity($entity_or_class, ObjectManager $objectManager)
    {
        if (is_object($entity_or_class)) {
            $entity = $entity_or_class;
            $class = $this->getClass($entity);
        } else {
            $class = $objectManager->getRepository($entity_or_class)->getClassName();
            $reflClass = new \ReflectionClass($class);

            if ($reflClass->isAbstract()) {
                return false;
            }

            $entity = $reflClass->newInstanceWithoutConstructor();
        }

        // check if we already saw this type of entity
        // to avoid some expensive work
        if (!array_key_exists($class, self::$indexSettings)) {
            self::$indexSettings[$class] = $this->getMetaDataLoader()->getMetaData($entity, $objectManager);
        }

        return false !== self::$indexSettings[$class];
    }

    /**
     * Tells us whether we need to autoindex this entity.
     * @internal
     */
    public function autoIndex($entity, ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;

        if (!$this->discoverEntity($entity, $objectManager)) {
            return false;
        } else {
            return self::$indexSettings[$this->getClass($entity)]->getIndex()->getAutoIndex();
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
        $class = $this->getClass($entity);

        $needsIndexing = true;
        $wasIndexed = true;

        if ($this->isEmbeddedObject($entity)) {
            return false;
        }

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
        foreach (self::$indexSettings[$this->getClass($entity)]->getIndexIfs() as $if) {
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
        if ($checkShouldIndex && !$this->shouldIndex(is_object($entity) ? $entity : $entity['entity'])) {
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


    public function isEntity(ObjectManager $objectManager, $class)
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return ! $objectManager->getMetadataFactory()->isTransient($class);
    }

    private function extractPropertyValue($entity, $field, $depth)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $value = $accessor->getValue($entity, $field);

        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            if ($depth >= 2 && !$this->isEmbeddedObject($entity)) {
                return null;
            }

            $value = $value->toArray();

            if (count($value) > 0) {
                if (! $this->discoverEntity(reset($value), $this->objectManager)) {
                    throw new NotAnAlgoliaEntity(
                        'Tried to index `'.$field.'` relation which is a `'.get_class(reset($value)).'` instance, which is not recognized as an entity to index.'
                    );
                }
            }

            $value = array_map(function ($val) use ($depth) {
                return $this->getFieldsForAlgolia($val, null, $depth + 1);
            }, $value);
        }

        if (is_object($value) && $this->isEntity($this->objectManager, $value)) {
            if ($depth >= 2 && !$this->isEmbeddedObject($entity)) {
                return null;
            }

            if (! $this->discoverEntity($value, $this->objectManager)) {
                throw new NotAnAlgoliaEntity(
                    'Tried to index `'.$field.'` relation which is a `'.get_class($value).'` instance, which is not recognized as an entity to index.'
                );
            }

            $value = $this->getFieldsForAlgolia($value, null, $depth + 1);
        }



        return $value;
    }

    /**
     * @internal
     * Returns a pair of json encoded arrays [newPrimaryKey, oldPrimaryKey]
     * Where oldPrimaryKey is null if the primary key did not change,
     * which is most of the times!
     */
    public function getPrimaryKeyForAlgolia($entity, array $changeSet = null, $depth = 0)
    {
        $class = $this->getClass($entity);
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
                $old = $new = $this->extractPropertyValue($entity, $fieldName, $depth);
            }

            if (!$new) {
                throw new NoPrimaryKey(
                    "An entity without a valid primary key was found during synchronization with Algolia."
                );
            }

            $oldPrimaryKeyValues[$fieldName] = $old;
            $newPrimaryKeyValues[$fieldName] = $new;
        }

        $primaryKey = $this->serializePrimaryKey($newPrimaryKeyValues);
        $oldPrimaryKey = $changed ? $this->serializePrimaryKey($oldPrimaryKeyValues) : null;

        return array($primaryKey, $oldPrimaryKey);
    }

    /**
     * @todo: This function should be made simpler,
     * but it seems currently the PHP client library fails
     * to decode responses from Algolia when we put JSON or
     * serialized objects in the objectIDs.
     *
     * Tests have been adapted to use this function too,
     * so changing it to something else should not break any test.
     * @internal
     *
     */
    public function serializePrimaryKey(array $values)
    {
        return base64_encode(json_encode($values));
    }

    /**
     * @internal
     */
    public function unserializePrimaryKey($pkey)
    {
        return json_decode(base64_decode($pkey), true);
    }

    /**
     * @internal
     */
    public function getFieldsForAlgolia($entity, array $changeSet = null, $depth = 0)
    {
        $class = $this->getClass($entity);

        if (!isset(self::$indexSettings[$class])) {
            throw new UnknownEntity("Entity of class `$class` is not known to Algolia. This is likely an implementation bug.");
        }

        $fields = array();

        // Get fields coming from properties
        foreach (self::$indexSettings[$class]->getProperties() as $prop) {
            $fields[$prop->getAlgoliaName()] = $this->extractPropertyValue($entity, $prop->getName(), $depth);
        }

        // Get fields coming from methods
        foreach (self::$indexSettings[$class]->getMethods() as $meth) {
            $fields[$meth->getAlgoliaName()] = $meth->evaluate($entity);
        }

        return $fields;
    }

    /**
     * @internal
     */
    public function getAlgoliaIndexName($entity_or_class)
    {
        $class = is_object($entity_or_class) ? $this->getClass($entity_or_class) : $entity_or_class;

        if (!isset(self::$indexSettings[$class])) {
            throw new UnknownEntity("Entity $class is not known to Algolia. This is likely an implementation bug.");
        }

        $index = self::$indexSettings[$class]->getIndex();
        $indexName = $index->getAlgoliaName();

        if (!empty($this->indexNamePrefix)) {
            $indexName = $this->indexNamePrefix . '_' . $indexName;
        }

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
            if (is_object($entity)) {
                $index = $this->getAlgoliaIndexName($entity);
            } else {
                $index = $entity['indexName'];
                $entity = $entity['entity'];
            }

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
    }

    /**
     * Keep track of a remote task to be able to wait for it later.
     * Since it is enough to check that the task with the higher taskID is complete to
     * conclude that tasks with lower taskID's are done, we only store the latest one.
     *
     * We also store the index object itself, that way, when we call waitForAlgoliaTasks,
     * we don't have to call getIndex, which would otherwise create the index in some cases.
     * This makes sure we don't accidentally create an index when just waiting for its deletion.
     *
     * @internal
     */
    public function algoliaTask($indexName, $res)
    {
        if (!empty($res['taskID'])) {
            if (!isset($this->latestAlgoliaTaskID[$indexName]) || $res['taskID'] > $this->latestAlgoliaTaskID[$indexName]['taskID']) {
                $this->latestAlgoliaTaskID[$indexName] = [
                    'index' => $this->getIndex($indexName),
                    'taskID' => $res['taskID']
                ];
            }
        }

        return $res;
    }

    /**
     * This function does creations or updates - it sends full resources,
     * whether new or updated.
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
                $this->getIndex($indexName)->saveObjects($objects)
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

    public function getManualIndexer(ObjectManager $em)
    {
        return new ManualIndexer($this, $em);
    }

    /**
     * Return a properly configured instance of the Algolia PHP client library
     * and caches it.
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \AlgoliaSearch\Client(
                $this->apiSettings['application_id'],
                $this->apiSettings['api_key']
            );

            if (isset($this->apiSettings['connection_timeout'])) {
                $this->client->setConnectTimeout(
                    $this->apiSettings['connection_timeout']
                );
            }
        }


        return $this->client;
    }

    /**
     * Returns an object used to communicate with the Algolia indexes
     * and caches it.
     * @internal
     */
    public function getIndex($indexName)
    {
        if (!isset($this->indices[$indexName])) {
            $this->indices[$indexName] = $this->getClient()->initIndex($indexName);
        }

        return $this->indices[$indexName];
    }

    /**
     * Add the correct environment suffix to an index name,
     * this is primarily used by rawSearch as in rawSearch we don't want
     * the user to bother about knowing the environment he's on.
     * @internal
     */
    public function makeEnvIndexName($indexName, $perEnvironment)
    {
        if ($perEnvironment) {
            return $indexName . '_' . $this->environment;
        } else {
            return $indexName;
        }
    }

    /**
     * Performs a raw search in the Algolia indexes, i.e. will not involve
     * the local DB at all, and only return what's indexed on Algolia's servers.
     *
     * @param  string       $indexName   The name of the index to search from.
     * @param  string       $queryString The query string.
     * @param  array        $options     Any search option understood by https://github.com/algolia/algoliasearch-client-php, plus:
     *                                   - perEnvironment: automatically suffix the index name with the environment, defaults to true
     *                                   - adaptIndexName: transform the index name as needed (e.g. add environment suffix), defaults to true.
     *                                   This option is here because sometimes we already have the suffixed index name, so calling rawSearch with
     *                                   adaptIndexName = false ensures we end up with the correct Algolia index name.
     * @return SearchResult The results returned by Algolia. The `isHydrated` method of the result will return false.
     */
    public function rawSearch($indexName, $queryString, array $options = array())
    {
        $defaultOptions = [
            'perEnvironment' => true,
            'adaptIndexName' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $client = $this->getClient();

        if ($options['adaptIndexName']) {
            $indexName = $this->makeEnvIndexName($indexName, $options['perEnvironment']);
        }

        // these are not a real search option:
        unset($options['perEnvironment']);
        unset($options['adaptIndexName']);

        $index = $this->getIndex($indexName);

        return new SearchResult($index->search($queryString, $options));
    }

    /**
     * Perform a 'native' search on the Algolia servers.
     * 'Native' means that once the results are retrieved, they will be fetched from the local DB
     * and replaced with native ORM entities.
     *
     * @param  ObjectManager $em          The Doctrine Entity Manager to use to fetch entities when hydrating the results.
     * @param  string        $indexName   The name of the index to search from.
     * @param  string        $queryString The query string.
     * @param  array         $options     Any search option understood by https://github.com/algolia/algoliasearch-client-php
     * @return SearchResult  The results returned by Algolia. The `isHydrated` method of the result will return true.
     */
    public function search(ObjectManager $em, $entityName, $queryString, array $options = array())
    {
        $entityClass = $em->getRepository($entityName)->getClassName();

        if (!$this->discoverEntity($entityClass, $em)) {
            throw new NotAnAlgoliaEntity(
                'Can\'t search, entity of class `'.$entityClass.'` is not recognized as an Algolia enriched entity.'
            );
        }

        // We're already finding the right index ourselves.
        $options['adaptIndexName'] = false;

        $indexName = $this->getAlgoliaIndexName($entityClass);

        // get results from Algolia
        $results = $this->rawSearch($indexName, $queryString, $options);

        $hydratedHits = [];

        // hydrate them as Doctrine entities
        foreach ($results->getHits() as $result) {
            $id = $this->unserializePrimaryKey($result['objectID']);
            $entity = $em->find($entityClass, $id);
            $hydratedHits[] = $entity;
        }

        return new SearchResult($results->getOriginalResult(), $hydratedHits);
    }

    /**
     * @internal
     */
    public function deleteIndex($indexName, array $options = array())
    {
        $defaultOptions = [
            'perEnvironment' => true,
            'adaptIndexName' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $client = $this->getClient();

        if ($options['adaptIndexName']) {
            $indexName = $this->makeEnvIndexName($indexName, $options['perEnvironment']);
        }

        $this->algoliaTask(
            $indexName,
            $this->getClient()->deleteIndex($indexName)
        );

        if (isset($this->indices[$indexName])) {
            unset($this->indices[$indexName]);
        }

        return $this;
    }

    /**
     * @internal
     */
    public function setIndexSettings($indexName, array $settings, array $options = array())
    {
        $defaultOptions = [
            'perEnvironment' => true,
            'adaptIndexName' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $client = $this->getClient();

        if ($options['adaptIndexName']) {
            $indexName = $this->makeEnvIndexName($indexName, $options['perEnvironment']);
        }

        $this->algoliaTask(
            $indexName,
            $this->getIndex($indexName)->setSettings($settings)
        );

        return $this;
    }

    /**
     * Wait for all Algolia tasks recorded by `algoliaTask` to complete.
     */
    public function waitForAlgoliaTasks()
    {
        foreach ($this->latestAlgoliaTaskID as $indexName => $data) {
            $data['index']->waitTask($data['taskID']);
            unset($this->latestAlgoliaTaskID[$indexName]);
        }

        return $this;
    }

    /**
     * @param $entity
     * @return bool
     */
    private function isEmbeddedObject($entity)
    {
        if (! $this->objectManager instanceof DocumentManager) {
            return false;
        }

        $classMetadata = $this->objectManager->getClassMetadata($this->getClass($entity));
        return $classMetadata->isEmbeddedDocument;
    }
}
