<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\SearchService;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

/**
 * @internal
 */
final class SearchIndexerSubscriber implements EventSubscriber
{
    /**
     * @var SearchService
     */
    private $searchService;

    /**
     * @var array<int, string>
     */
    private $subscribedEvents;

    /**
     * @param SearchService      $searchService
     * @param array<int, string> $subscribedEvents
     */
    public function __construct(SearchService $searchService, $subscribedEvents)
    {
        $this->searchService     = $searchService;
        $this->subscribedEvents  = $subscribedEvents;
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
        $this->searchService->index($args->getObject(), $args->getObjectManager());
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->searchService->index($args->getObject(), $args->getObjectManager());
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->searchService->remove($object = $args->getObject(), $args->getObjectManager());
    }
}
