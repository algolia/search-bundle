<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class AlgoliaIntegrationTest extends BaseTest
{
	/**
	 * Here we really want to test the full integration
	 * and talk with Algolia servers.
	 */
	public static $isolateFromAlgolia = false;

	public function testNewProductIsIndexedAndRetrieved()
	{
		$product = new Entity\ProductForAlgoliaIntegrationTest();

		$product
		->setName('My First Product')
		->setShortDescription('Is Awesome.')
		->setDescription('Let me index it for you.')
		->setPrice(9.99)
		->setRating(10);

		$this->persistAndFlush($product);

		
	}
}
