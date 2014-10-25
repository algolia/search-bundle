<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping;

class Method extends Helper\ChangeAwareMethod
{
	private $algoliaName;

	public function setAlgoliaName($algoliaName)
	{
		$this->algoliaName = $algoliaName;

		return $this;
	}

	public function getAlgoliaName()
	{
		return $this->algoliaName;
	}
}