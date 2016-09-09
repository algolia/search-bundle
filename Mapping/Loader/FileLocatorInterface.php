<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

interface FileLocatorInterface
{
    /**
     * @param \ReflectionClass $class
     * @param string           $extension
     *
     * @return string|null
     */
    public function findFileForClass(\ReflectionClass $class, $extension);
}