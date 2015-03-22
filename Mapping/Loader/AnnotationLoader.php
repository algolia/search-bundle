<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Loader;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\ObjectManager;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation\Index as IndexAnnotation;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation\Attribute as IndexedAttributeAnnotation;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation\IndexIf as IndexIfAnnotation;

use Algolia\AlgoliaSearchBundle\Mapping\Index;
use Algolia\AlgoliaSearchBundle\Mapping\IndexIf;
use Algolia\AlgoliaSearchBundle\Mapping\Property;
use Algolia\AlgoliaSearchBundle\Mapping\Method;
use Algolia\AlgoliaSearchBundle\Mapping\Description;

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
    public function getMetaData($entity, ObjectManager $em)
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
                if ($annotation instanceof IndexedAttributeAnnotation) {
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
                if ($annotation instanceof IndexedAttributeAnnotation) {
                    $field = new Method();
                    $field->setName($meth->getName());

                    if ($annotation->algoliaName) {
                        $field->setAlgoliaName($annotation->algoliaName);
                    } else {
                        $field->setAlgoliaName(lcfirst(preg_replace('/^get([A-Z])/', '$1', $field->getName())));
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
            $description->setIdentifierAttributeNames($meta->getIdentifierFieldNames());

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
