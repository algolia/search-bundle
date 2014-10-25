<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class AlgoliaIntegrationTest extends BaseTest
{
	/**
	 * Here we really want to test the full integration
	 * and talk with Algolia servers.
	 */
	public static $isolateFromAlgolia = false;

	public function tearDown()
	{
		parent::tearDown();
		$this->getIndexer()->deleteIndex('ProductForAlgoliaIntegrationTest');
	}

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

		$this->getIndexer()->waitForAlgoliaTasks();
		
		$results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
		
		$this->assertEquals(1, $results['nbHits']);
		$this->assertEquals($product->getId(), $results['hits'][0]['objectID']);
	}

	public function testUpdatedProductIsIndexedAndRetrieved()
	{
		$product = new Entity\ProductForAlgoliaIntegrationTest();

		$product
		->setName('My First Product')
		->setShortDescription('Is Awesome.')
		->setDescription('Let me index it for you.')
		->setPrice(9.99)
		->setRating(10);

		$this->persistAndFlush($product);

		$product->setName('Totally Different Name.');
		$this->persistAndFlush($product);

		$this->getIndexer()->waitForAlgoliaTasks();

		$results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'Totally Different Name.');

		$this->assertEquals(1, $results['nbHits']);
		$this->assertEquals($product->getId(), $results['hits'][0]['objectID']);
		$this->assertEquals('Totally Different Name.', $results['hits'][0]['name']);
	}

	public function testProductIsUnindexed()
	{
		// I'm paranoid: check that our product is not indexed yet (from another test or so)
		$results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
		$this->assertEquals(0, $results['nbHits']);

		$product = new Entity\ProductForAlgoliaIntegrationTest();

		$product
		->setName('My First Product')
		->setShortDescription('Is Awesome.')
		->setDescription('Let me index it for you.')
		->setPrice(9.99)
		->setRating(10);

		$this->persistAndFlush($product);

		// Check that the product is indexed!
		$this->getIndexer()->waitForAlgoliaTasks();
		$results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
		$this->assertEquals(1, $results['nbHits']);
		$this->assertEquals($product->getId(), $results['hits'][0]['objectID']);

		$this->removeAndFlush($product);
		$this->getIndexer()->waitForAlgoliaTasks();
		$results = $this->getIndexer()->search('ProductForAlgoliaIntegrationTest', 'My First Product');
		$this->assertEquals(0, $results['nbHits']);
	}
}
