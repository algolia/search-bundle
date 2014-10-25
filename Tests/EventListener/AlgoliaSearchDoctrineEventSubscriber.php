<?php
namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\EventListener\AlgoliaSearchDoctrineEventSubscriber as RealEventSubscriber;

class AlgoliaSearchDoctrineEventSubscriber extends RealEventSubscriber
{
	public function create($entity)
	{
		if ($entity instanceof \Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\BaseTestAwareEntity) {
			$entity->setTestProp('create_callback', 'called');
		}

		parent::create($entity);
	}

	public function update($entity, $changeSet)
	{
		if ($entity instanceof \Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\BaseTestAwareEntity) {
			$entity->setTestProp('update_callback', 'called');
		}

		parent::update($entity, $changeSet);
	}

	public function delete($entity, $originalData)
	{
		if ($entity instanceof \Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity\BaseTestAwareEntity) {
			$entity->setTestProp('delete_callback', 'called');
		}

		parent::delete($entity, $originalData);
	}
}