<?php
namespace Algolia\AlgoliaSearchBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Algolia\AlgoliaSearchBundle\Indexer\Indexer;

class AlgoliaSearchDoctrineEventSubscriber implements EventSubscriber
{
    private $indexer;

    /**
     * Under normal circumstances, the service loader will set the indexer.
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
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
    public function onFlush(OnFlushEventArgs $args)
    {
        /**
         * There might have been an exception thrown during the previous flush attempt,
         * because the DB rejected our changes for instance.
         * We clean our indexer cache to prevent double indexing stuff if this happened.
         */
        $this->indexer->removeScheduledIndexChanges();

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->indexer->autoIndex($entity, $em)) {
                $this->create($entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->indexer->autoIndex($entity, $em)) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $this->update($entity, $changeSet);
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->indexer->autoIndex($entity, $em)) {
                $originalData = $uow->getOriginalEntityData($entity);
                $this->delete($entity, $originalData);
            }
        }

        /**
         * There are also:
         *
         * $uow->getScheduledCollectionDeletions();
         * $uow->getScheduledCollectionUpdates();
         *
         * But they're not relevant here, I think.
         *
         * Apparently they're used for internal bookkeeping when
         * doing things with Many-To-Many relationships.
         *
         * Leaving the comment just in case I'm wrong.
         */
    }

    /**
     * Real work happens here, but it is delegated to the indexer.
     */
    public function postFlush($ignoredArgument)
    {
        $this->indexer->processScheduledIndexChanges();
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
}
