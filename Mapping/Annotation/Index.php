<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Index
{
	/**
	 * @var  string
	 */
	public $algoliaName;

	/**
	 * @var  boolean
	 */
	public $perEnvironment = true;

	/**
	 * @var  boolean
	 */
	public $autoIndex = true;

	public function toArray()
	{
		$settings = [
			'perEnvironment' => $this->perEnvironment,
			'autoIndex' => $this->autoIndex
		];

		if ($this->algoliaName) {
			$settings['algoliaName'] = $this->algoliaName;
		}

		return $settings;
	}
}