<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\IndexManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class SearchIndexerSubscriber implements EventSubscriber
{
    protected $indexManager;

    protected $subscribedEvents;

    public function __construct(IndexManagerInterface $indexManager, $subscribedEvents)
    {
        $this->indexManager = $indexManager;
        $this->subscribedEvents = $subscribedEvents;
    }

    public function getSubscribedEvents()
    {
        return $this->subscribedEvents;
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $objectManager = $args->getObjectManager();

        if (!$this->indexManager->isSearchable($object)) {
            return;
        }

        if ($this->indexManager->shouldBeIndexed($object)) {
            $this->indexManager->index($object, $objectManager);
        } else {
            $this->indexManager->remove($object, $objectManager);
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $objectManager = $args->getObjectManager();

        if (!$this->indexManager->isSearchable($object)) {
            return;
        }

        if ($this->indexManager->shouldBeIndexed($object)) {
            $this->indexManager->index($object, $objectManager);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if ($this->indexManager->isSearchable($object)) {
            $this->indexManager->remove($object, $args->getObjectManager());
        }
    }
}
