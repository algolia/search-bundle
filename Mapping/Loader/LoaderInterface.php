<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Doctrine\ORM\EntityManager;

interface LoaderInterface
{
    /**
     * Extracts the Algolia metaData from an entity.
     *
     * @return Algolia\AlgoliaSearchBundle\Mapping\Description
     */
    public function getMetaData($entity, EntityManager $em);
}
