<?php

namespace Algolia\AlgoliaSearchBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

class AlgoliaSearchDoctrineORMEventSubscriber extends AbstractDoctrineEventSubscriber
{
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager($args)
    {
        if (! $args instanceof OnFlushEventArgs) {
          throw new \LogicException('Invalid onFlushEventArgs object encountered');
        }

        return $args->getEntityManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof EntityManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return $objectManager->getUnitOfWork()->getScheduledEntityInsertions();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof EntityManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return array_map(
            function ($entity) use ($objectManager) {
                return [$entity, $objectManager->getUnitOfWork()->getEntityChangeSet($entity)];
            },
            $objectManager->getUnitOfWork()->getScheduledEntityUpdates()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof EntityManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return array_map(
            function ($entity) use ($objectManager) {
                return [$entity, $objectManager->getUnitOfWork()->getOriginalEntityData($entity)];
            },
            $objectManager->getUnitOfWork()->getScheduledEntityDeletions()
        );
    }
}
