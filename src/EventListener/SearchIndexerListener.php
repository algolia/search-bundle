<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\SearchService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preRemove)]
final class SearchIndexerListener
{
    /**
     * @var SearchService
     */
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService     = $searchService;
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
