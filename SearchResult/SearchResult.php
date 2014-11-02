<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\SearchResult;

class SearchResult
{
	private $originalResult;
	private $hydrated;
	private $maybeHydratedHits;

	public function __construct(array $result, array $hydratedHits = null)
	{
		$this->originalResult = $result;

		if ($hydratedHits === null) {
			$this->hydrated = false;
		} else {
			$this->maybeHydratedHits = $hydratedHits;
			$this->hydrated = true;
		}
	}

	public function isHydrated()
	{
		return $this->hydrated;
	}

	public function getHits()
	{
		if ($this->isHydrated()) {
			return $this->maybeHydratedHits;
		} else {
			return $this->originalResult['hits'];
		}
	}

	public function getHit($n)
	{
		return $this->getHits()[$n];
	}

	public function getNbHits()
	{
		return $this->originalResult['nbHits'];
	}

	public function getPage()
	{
		return $this->originalResult['page'];
	}

	public function getNbPages()
	{
		return $this->originalResult['nbPages'];
	}

	public function getHitsPerPage()
	{
		return $this->originalResult['hitsPerPage'];
	}

	public function getProcessingTimeMS()
	{
		return $this->originalResult['processingTimeMS'];
	}

	public function getQuery()
	{
		return $this->originalResult['query'];
	}

	public function getParams()
	{
		return $this->originalResult['params'];
	}

	public function getOriginalResult()
	{
		return $this->originalResult;
	}
}