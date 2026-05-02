<?php

namespace Algolia\SearchBundle\EventListener;

use Algolia\SearchBundle\SearchService;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;

class SearchIndexerSubscriber
{
    private array $objectsToIndex  = [];
    private array $objectsToRemove = [];

    public function __construct(
        private readonly SearchService $searchService,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->objectsToIndex[] = $args->getObject();
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->objectsToIndex[] = $args->getObject();
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->objectsToRemove[] = $args->getObject();
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->objectsToIndex as $object) {
            $this->searchService->index($args->getObjectManager(), $object);
        }
        $this->objectsToIndex = [];

        foreach ($this->objectsToRemove as $object) {
            $this->searchService->remove($args->getObjectManager(), $object);
        }
        $this->objectsToRemove = [];
    }
}
