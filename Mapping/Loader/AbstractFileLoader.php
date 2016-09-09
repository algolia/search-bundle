<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Doctrine\ORM\EntityManager;

abstract class AbstractFileLoader implements LoaderInterface
{
    /**
     * @var FileLocatorInterface|FileLocator
     */
    private $locator;

    public function __construct(FileLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getMetaData($entity, EntityManager $em)
    {
        $class = new \ReflectionClass($entity);
        if (null === $path = $this->locator->findFileForClass($class, $this->getExtension())) {
            return null;
        }

        return $this->loadMetadataFromFile($class, $path);
    }

    /**
     * Parses the content of the file, and converts it to the desired metadata.
     *
     * @param \ReflectionClass $class
     * @param string           $file
     */
    abstract protected function loadMetadataFromFile(\ReflectionClass $class, $file);

    /**
     * Returns the extension of the file.
     *
     * @return string
     */
    abstract protected function getExtension();
}
