<?php
namespace Algolia\AlgoliaSearchBundle\EventListener;

use Algolia\AlgoliaSearchBundle\Indexer\Indexer;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Log\LoggerInterface;

class AlgoliaSearchDoctrineEventSubscriber implements EventSubscriber
{
    private $indexer;
    private $logger;
    private $catchAndLogExceptions;

    /**
     * Under normal circumstances, the service loader will set the indexer.
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer, $catchAndLogExceptions, LoggerInterface $logger = null)
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
     * During onFlush, we tell the indexer what it should
     * index or unindex right after the data has been committed to the DB.
     *
     * By right after, I mean during the postFlush callback.
     * This is done to avoid sending wrong data to Algolia
     * if the local DB rejected our changes.
     */
    public function onFlush($args)
    {
        switch (true) {
            case $args instanceof ManagerEventArgs:
                $objectManager = $args->getObjectManager();
                break;

            case $args instanceof OnFlushEventArgs:
                $objectManager = $args->getEntityManager();
                break;

            default:
                throw new \LogicException('Invalid onFlushEventArgs object encountered');
        }

        try {
            /**
             * There might have been an exception thrown during the previous flush attempt,
             * because the DB rejected our changes for instance.
             * We clean our indexer cache to prevent double indexing stuff if this happened.
             */
            $this->indexer->removeScheduledIndexChanges();

            foreach ($this->getScheduledObjectInsertions($objectManager) as $object) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->create($object);
                }
            }

            foreach ($this->getScheduledObjectUpdates($objectManager) as list($object, $changeSet)) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->update($object, $changeSet);
                }
            }

            foreach ($this->getScheduledObjectDeletions($objectManager) as list($object, $originalData)) {
                if ($this->indexer->autoIndex($object, $objectManager)) {
                    $this->delete($object, $originalData);
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

    protected function create($entity)
    {
        $this->indexer->scheduleEntityCreation($entity);
    }

    protected function update($entity, $changeSet)
    {
        $this->indexer->scheduleEntityUpdate($entity, $changeSet);
    }

    protected function delete($entity, $originalData)
    {
        $this->indexer->scheduleEntityDeletion($entity, $originalData);
    }

    private function getScheduledObjectInsertions(ObjectManager $objectManager)
    {
        switch (true) {
            case $objectManager instanceof EntityManager:
                return $objectManager->getUnitOfWork()->getScheduledEntityInsertions();
                break;

            case $objectManager instanceof DocumentManager:
                return array_merge(
                    $objectManager->getUnitOfWork()->getScheduledDocumentInsertions(),
                    $objectManager->getUnitOfWork()->getScheduledDocumentUpserts()
                );
                break;

            default:
                throw new \LogicException('Unsupported document manager given');
        }
    }

    private function getScheduledObjectUpdates(ObjectManager $objectManager)
    {
        switch (true) {
            case $objectManager instanceof EntityManager:
                return array_map(
                    function ($entity) use ($objectManager) {
                        return [$entity, $objectManager->getUnitOfWork()->getEntityChangeSet($entity)];
                    },
                    $objectManager->getUnitOfWork()->getScheduledEntityUpdates()
                );
                break;

            case $objectManager instanceof DocumentManager:
                return array_map(
                    function ($document) use ($objectManager) {
                        return [$document, $objectManager->getUnitOfWork()->getDocumentChangeSet($document)];
                    },
                    $objectManager->getUnitOfWork()->getScheduledDocumentUpdates()
                );
                break;

            default:
                throw new \LogicException('Unsupported document manager given');
        }
    }

    private function getScheduledObjectDeletions(ObjectManager $objectManager)
    {
        switch (true) {
            case $objectManager instanceof EntityManager:
                return array_map(
                    function ($entity) use ($objectManager) {
                        return [$entity, $objectManager->getUnitOfWork()->getOriginalEntityData($entity)];
                    },
                    $objectManager->getUnitOfWork()->getScheduledEntityDeletions()
                );
                break;

            case $objectManager instanceof DocumentManager:
                return array_map(
                    function ($document) use ($objectManager) {
                        return [$document, $objectManager->getUnitOfWork()->getOriginalDocumentData($document)];
                    },
                    $objectManager->getUnitOfWork()->getScheduledDocumentDeletions()
                );
                break;

            default:
                throw new \LogicException('Unsupported document manager given');
        }
    }
}
