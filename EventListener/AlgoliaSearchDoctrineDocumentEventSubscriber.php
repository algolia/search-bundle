<?php
namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\EventListener;

use Doctrine\Common\EventSubscriber;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Indexer\Indexer;

class AlgoliaSearchDoctrineDocumentEventSubscriber implements EventSubscriber
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
    public function onFlush($args)
    {
        /**
         * There might have been an exception thrown during the previous flush attempt,
         * because the DB rejected our changes for instance.
         * We clean our indexer cache to prevent double indexing stuff if this happened.
         */
        $this->indexer->removeScheduledIndexChanges();

       
        $dm = $args->getDocumentManager();

        $uow = $dm->getUnitOfWork();

        foreach ($uow->getScheduledDocumentInsertions() as $entity) {
            print_r($entity);
            if ($this->indexer->autoIndex($entity, $dm)) {
                $this->create($entity);
            } else {
                echo "\nNOOOPE!\n";
            }
        }

        foreach ($uow->getScheduledDocumentUpdates() as $entity) {
            if ($this->indexer->autoIndex($entity, $dm)) {
                $changeSet = $uow->getDocumentChangeSet($entity);
                $this->update($entity, $changeSet);
            }
        }

        foreach ($uow->getScheduledDocumentDeletions() as $entity) {
            if ($this->indexer->autoIndex($entity, $dm)) {
                $originalData = $uow->getOriginalDocumentData($entity);
                $this->delete($entity, $originalData);
            }
        }
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
