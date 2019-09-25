<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\IndexManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

final class SearchIndexerSubscriber implements EventSubscriber
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * @var array<int, string>
     */
    private $subscribedEvents;

    /**
     * @param IndexManager       $indexManager
     * @param array<int, string> $subscribedEvents
     */
    public function __construct(IndexManager $indexManager, $subscribedEvents)
    {
        $this->indexManager     = $indexManager;
        $this->subscribedEvents = $subscribedEvents;
    }

    /**
     * @return array<int, string>
     */
    public function getSubscribedEvents()
    {
        return $this->subscribedEvents;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->indexManager->index($args->getObject(), $args->getObjectManager());
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->indexManager->index($args->getObject(), $args->getObjectManager());
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->indexManager->remove($object = $args->getObject(), $args->getObjectManager());
    }
}
