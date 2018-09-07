<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\IndexManagerInterface;
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

        if ($this->indexManager->isSearchable($object) || (method_exists($this->indexManager, 'belongsToOneAggregator') && $this->indexManager->belongsToOneAggregator($object))) {
            $this->indexManager->index($object, $args->getObjectManager());
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if ($this->indexManager->isSearchable($object) || (method_exists($this->indexManager, 'belongsToOneAggregator') && $this->indexManager->belongsToOneAggregator($object))) {
            $this->indexManager->index($object, $args->getObjectManager());
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if ($this->indexManager->isSearchable($object) || (method_exists($this->indexManager, 'belongsToOneAggregator') && $this->indexManager->belongsToOneAggregator($object))) {
            $this->indexManager->remove($object, $args->getObjectManager());
        }
    }
}
