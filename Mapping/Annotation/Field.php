<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class Field
{
	/**
	 * @var  string
	 */
	public $algoliaName;
}