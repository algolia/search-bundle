<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\SearchService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;

class SearchIndexerSubscriber
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
    public function postPersist(PostPersistEventArgs $args)
    {
        $this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    /**
     * @return void
     */
    public function postUpdate(PostUpdateEventArgs $args)
    {
        $this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    /**
     * @return void
     */
    public function preRemove(PreRemoveEventArgs $args)
    {
        $this->searchService->remove($args->getObjectManager(), $object = $args->getObject());
    }
}
