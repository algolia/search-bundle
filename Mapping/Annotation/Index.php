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

	/**
	 * @var  integer
	 */
	public $minWordSizefor1Typo;

	/**
	 * @var  integer
	 */
	public $minWordSizefor2Typos;

	/**
	 * @var  integer
	 */
	public $hitsPerPage;

	/**
	 * @var  array<string>
	 */
	public $attributesToIndex;

	/**
	 * @var  array<string>
	 */
	public $attributesToRetrieve;

	/**
	 * @var  array<string>
	 */
	public $unretrievableAttributes;

	/**
	 * @var  array<string>
	 */
	public $optionalWords;

	/**
	 * @var  array<string>
	 */
	public $attributesForFaceting;

	/**
	 * @var  array<string>
	 */
	public $attributesToSnippet;

	/**
	 * @var  array<string>
	 */
	public $attributesToHighlight;

	/**
	 * @var  string
	 */
	public $attributeForDistinct;

	/**
	 * @var  array<string>
	 */
	public $ranking;

	/**
	 * @var  array<string>
	 */
	public $slaves;

	/**
	 * @var  array<string>
	 */
	public $customRanking;

	/**
	 * @var  string
	 */
	public $separatorsToIndex;

	/**
	 * @var  string
	 */
	public $removeWordsIfNoResults;

	/**
	 * @var  string
	 */
	public $queryType;

	/**
	 * @var  string
	 */
	public $highlightPreTag;

	/**
	 * @var  string
	 */
	public $highlightPostTag;

	public function toArray()
	{
		$settings = [
			'perEnvironment' => $this->perEnvironment,
			'autoIndex' => $this->autoIndex
		];

		if ($this->algoliaName) {
			$settings['algoliaName'] = $this->algoliaName;
		}

		foreach (\Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Index::$algoliaSettingsProps as $field) {
			$settings[$field] = $this->$field;
		}

		return $settings;
	}
}