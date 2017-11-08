<?php

namespace Algolia\SearchBundle\EventListener;


use Algolia\SearchBundle\Searchable\IndexManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class SearchIndexerSubscriber implements EventSubscriber
{
    protected $indexManager;

    public function __construct(IndexManager $indexManager)
    {
        $this->indexManager = $indexManager;
    }

    public function getSubscribedEvents()
    {
        return array(
            'postPersist',
            'postUpdate',
            'preRemove',
        );
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $objectManager = $args->getObjectManager();

        $this->indexManager->index($object, $objectManager);
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        $this->indexManager->delete($object, $args->getObjectManager());
    }
}
