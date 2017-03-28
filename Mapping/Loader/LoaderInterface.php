<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Doctrine\Common\Persistence\ObjectManager;

interface LoaderInterface
{
    /**
     * Extracts the Algolia metaData from an entity.
     *
     * @return \Algolia\AlgoliaSearchBundle\Mapping\Description
     */
    public function getMetaData($entity, ObjectManager $objectManager);
}
