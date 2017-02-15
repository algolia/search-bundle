<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Doctrine\ORM\EntityManager;

final class LoaderChain implements LoaderInterface
{
    private $loaders;

    public function getMetaData($entity, EntityManager $em)
    {
        foreach ($this->loaders as $loader) {
            if (null !== $metadata = $loader->getMetaData($entity, $em)) {
                return $metadata;
            }
        }

        return null;
    }

    public function __construct(array $loaders = array())
    {
        $this->loaders = $loaders;
    }

    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }
}
