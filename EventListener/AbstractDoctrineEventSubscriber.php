<?php

namespace Algolia\AlgoliaSearchBundle\EventListener;

use Algolia\AlgoliaSearchBundle\Indexer\Indexer;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;

abstract class AbstractDoctrineEventSubscriber implements EventSubscriber
{
    private $indexer;
    private $logger;
    private $catchAndLogExceptions;

    /**
     * Under normal circumstances, the service loader will set the indexer.
     * @param Indexer $indexer
     * @param bool $catchAndLogExceptions
     * @param LoggerInterface|null $logger
     */
    public function __construct(Indexer $indexer, $catchAndLogExceptions = false, LoggerInterface $logger = null)
    {
        $this->indexer = $indexer;
        $this->catchAndLogExceptions = $catchAndLogExceptions;
        $this->logger = $logger;
    }

    /**
     * The events we're interested in.
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'postFlush'
        );
    }

    /**
     * @param \Doctrine\ORM\Event\OnFlushEventArgs|\Doctrine\ODM\MongoDB\Event\OnFlushEventArgs $args
     * @return ObjectManager
     */
    abstract protected function getObjectManager($args);

    /**
     * @param ObjectManager $objectManager
     * @return array Returns an array of objects to be inserted
     */
    abstract protected function getScheduledObjectInsertions(ObjectManager $objectManager);

    /**
     * @param ObjectManager $objectManager
     * @return array Returns an array of objects and changesets
     */
    abstract protected function getScheduledObjectUpdates(ObjectManager $objectManager);

    /**
     * @param ObjectManager $objectManager
     * @return array Returns an array of objects and original data
     */
    abstract protected function getScheduledObjectDeletions(ObjectManager $objectManager);

    /**
     * During onFlush, we tell the indexer what it should
     * index or unindex right after the data has been committed to the DB.
     *
     * By right after, I mean during the postFlush callback.
     * This is done to avoid sending wrong data to Algolia
     * if the local DB rejected our changes.
     */
    public function onFlush($args)
    {
        $objectManager = $this->getObjectManager($args);

        try {
            /**
             * There might have been an exception thrown during the previous flush attempt,
             * because the DB rejected our changes for instance.
             * We clean our indexer cache to prevent double indexing stuff if this happened.
             */
            $this->indexer->removeScheduledIndexChanges();

            foreach ($this->getScheduledObjectInsertions($objectManager) as $object) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->indexer->scheduleEntityCreation($object);
                }
            }

            foreach ($this->getScheduledObjectUpdates($objectManager) as list($object, $changeSet)) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->indexer->scheduleEntityUpdate($object, $changeSet);
                }
            }

            foreach ($this->getScheduledObjectDeletions($objectManager) as list($object, $originalData)) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->indexer->scheduleEntityDeletion($object, $originalData);
                }
            }
        } catch (\Exception $e) {
            if ($this->catchAndLogExceptions) {
                if ($this->logger) {
                    $this->logger->error('AlgoliaSearch: '.$e->getMessage());
                }
            } else {
                throw $e;
            }
        }
    }

    /**
     * Real work happens here, but it is delegated to the indexer.
     */
    public function postFlush($ignoredArgument)
    {
        try {
            $this->indexer->processScheduledIndexChanges();
        } catch (\Exception $e) {
            if ($this->catchAndLogExceptions) {
                if ($this->logger) {
                    $this->logger->error('AlgoliaSearch: '.$e->getMessage());
                }
            } else {
                throw $e;
            }
        }
    }
}
