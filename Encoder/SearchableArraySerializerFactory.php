<?php

namespace Algolia\SearchBundle\Encoder;


use Psr\Container\ContainerInterface;

class SearchableArraySerializerFactory
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($className)
    {
        $meta = $this->container
            ->get('doctrine')
            ->getManager()
            ->getClassMetadata($className);

        $normalizer = new EntityNormalizer($meta);

        // Todo
    }
}
