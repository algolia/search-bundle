<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\Service\SearchServiceInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

/**
 * @internal
 */
final class SearchIndexerSubscriber implements EventSubscriber
{
    /**
     * @var SearchServiceInterface
     */
    private $searchService;

    /**
     * @var array<int, string>
     */
    private $subscribedEvents;

    /**
     * @param array<int, string> $subscribedEvents
     */
    public function __construct(SearchServiceInterface $searchService, $subscribedEvents)
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
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    /**
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    /**
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->searchService->remove($args->getObjectManager(), $object = $args->getObject());
    }
}
