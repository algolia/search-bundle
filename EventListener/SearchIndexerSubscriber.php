<?php

namespace Algolia\SearchBundle\EventListener;


use Algolia\SearchBundle\Engine\AlgoliaIndexingEngine;
use Algolia\SearchBundle\Searchable\Searchable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class SearchIndexerSubscriber implements EventSubscriber
{
    protected $engine;

    public function __construct(AlgoliaIndexingEngine $engine)
    {
        $this->engine = $engine;
    }

    public function getSubscribedEvents()
    {
        return array(
            'postPersist',
            'postUpdate',
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
        $meta = $args->getObjectManager()->getClassMetadata(get_class($object));

        $searchableEntity = new Searchable($object, $meta);

        $this->engine->update($searchableEntity);
    }
}
