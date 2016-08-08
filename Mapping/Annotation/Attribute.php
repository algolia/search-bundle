<?php

namespace Algolia\AlgoliaSearchBundle\Mapping\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class Attribute
{
    /**
     * @var  string
     */
    public $algoliaName;
}
