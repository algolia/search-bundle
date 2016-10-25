<?php

namespace Algolia\AlgoliaSearchBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;

class AlgoliaSearchDoctrineMongoDBODMEventSubscriber extends AbstractDoctrineEventSubscriber
{
    /**
     * {@inheritdoc}
     */
    protected function getObjectManager($args)
    {
        if (! $args instanceof OnFlushEventArgs) {
            throw new \LogicException('Invalid onFlushEventArgs object encountered');
        }

        return $args->getDocumentManager();
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectInsertions(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof DocumentManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return array_merge(
            $objectManager->getUnitOfWork()->getScheduledDocumentInsertions(),
            $objectManager->getUnitOfWork()->getScheduledDocumentUpserts()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectUpdates(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof DocumentManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return array_map(
            function ($document) use ($objectManager) {
                return [$document, $objectManager->getUnitOfWork()->getDocumentChangeSet($document)];
            },
            $objectManager->getUnitOfWork()->getScheduledDocumentUpdates()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getScheduledObjectDeletions(ObjectManager $objectManager)
    {
        if (! $objectManager instanceof DocumentManager) {
            throw new \LogicException('Unsupported document manager given');
        }

        return array_map(
            function ($document) use ($objectManager) {
                return [$document, $objectManager->getUnitOfWork()->getOriginalDocumentData($document)];
            },
            $objectManager->getUnitOfWork()->getScheduledDocumentDeletions()
        );
    }
}
