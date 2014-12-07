<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Loader;

use Doctrine\Common\Persistence\ObjectManager;

interface LoaderInterface
{
    /**
	 * Extracts the Algolia metaData from an entity.
	 *
	 * @return Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Description
	 */
    public function getMetaData($entity, ObjectManager $em);
}
