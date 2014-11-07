<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation;

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
