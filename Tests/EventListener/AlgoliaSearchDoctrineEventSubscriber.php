<?php
namespace Algolia\AlgoliaSearchBundle\Tests\EventListener;

use Algolia\AlgoliaSearchBundle\EventListener\AlgoliaSearchDoctrineEventSubscriber as RealEventSubscriber;

class AlgoliaSearchDoctrineEventSubscriber extends RealEventSubscriber
{
    public function create($entity)
    {
        if ($entity instanceof \Algolia\AlgoliaSearchBundle\Tests\Entity\BaseTestAwareEntity) {
            $entity->setTestProp('create_callback', 'called');
        }

        parent::create($entity);
    }

    public function update($entity, $changeSet)
    {
        if ($entity instanceof \Algolia\AlgoliaSearchBundle\Tests\Entity\BaseTestAwareEntity) {
            $entity->setTestProp('update_callback', 'called');
        }

        parent::update($entity, $changeSet);
    }

    public function delete($entity, $originalData)
    {
        if ($entity instanceof \Algolia\AlgoliaSearchBundle\Tests\Entity\BaseTestAwareEntity) {
            $entity->setTestProp('delete_callback', 'called');
        }

        parent::delete($entity, $originalData);
    }
}
