<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Loader;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation\Index as IndexAnnotation;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation\Field as IndexedFieldAnnotation;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation\IndexIf as IndexIfAnnotation;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Index;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\IndexIf;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Property;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Method;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Description;

class AnnotationLoader implements LoaderInterface
{
    protected static $annotationReader;
    /**
     * Boilerplate code to get something to
     * read annotations.
     * @internal
     */
    protected function getAnnotationReader()
    {
        if (self::$annotationReader) {
            return self::$annotationReader;
        }

        $reader = new AnnotationReader();
        $cachedReader = new CachedReader($reader, new ArrayCache());
        
        self::$annotationReader = $cachedReader;

        return self::$annotationReader;
    }

    /**
     * @return Description
     */
    public function getMetaData($entity, EntityManager $em)
    {
        $class = get_class($entity);

        $description = new Description($class);

        $refl = new \ReflectionClass($entity);

        $reader = $this->getAnnotationReader();

        foreach ($reader->getClassAnnotations($refl) as $annotation) {
            if ($annotation instanceof IndexAnnotation) {
                $index = new Index();
                $index->setAlgoliaNameFromClass($class);
                $index->updateSettingsFromArray($annotation->toArray());
                $description->setIndex($index);
            }
        }

        foreach ($refl->getProperties() as $property) {
            foreach ($reader->getPropertyAnnotations($property) as $annotation) {
                if ($annotation instanceof IndexedFieldAnnotation) {
                    $field = new Property();
                    $field->setName($property->getName());
                
                    if ($annotation->algoliaName) {
                        $field->setAlgoliaName($annotation->algoliaName);
                    } else {
                        $field->setAlgoliaName($field->getName());
                    }

                    $description->addProperty($field);
                }
            }
        }
        foreach ($refl->getMethods() as $meth) {
            foreach ($reader->getMethodAnnotations($meth) as $annotation) {
                if ($annotation instanceof IndexedFieldAnnotation) {
                    $field = new Method();
                    $field->setName($meth->getName());
                
                    if ($annotation->algoliaName) {
                        $field->setAlgoliaName($annotation->algoliaName);
                    } else {
                        $field->setAlgoliaName(lcfirst(preg_replace('/^get/', '', $field->getName())));
                    }

                    $description->addMethod($field);
                }
                if ($annotation instanceof IndexIfAnnotation) {
                    $indexIf = new IndexIf();
                    $indexIf->setName($meth->getName());

                    $description->addIndexIf($indexIf);
                }
            }
        }

        if (!$description->isEmpty()) {

            $meta = $em->getClassMetadata($class);
            $description->setIdentifierFieldNames($meta->getIdentifierFieldNames());

            // In case the user omitted defining the index, define it for him with default values
            if (null === $description->getIndex()) {
                $index = new Index();
                $index->setAlgoliaNameFromClass($class);
                $description->setIndex($index);
            }

            return $description;

        } else {

            return false;

        }
    }
}