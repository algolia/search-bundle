<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\AlgoliaSearch\Response\IndexingResponse;
use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Engine;

class EngineTest extends BaseTest
{
    protected $engine;

    public function setUp(): void
    {
        parent::setUp();

        /* @var Engine */
        $this->engine = new Engine($this->get('search.client'));
    }

    /**
     * Doctrine is currently splitting the common package
     * into 3 separate ones, some deprecation notice appeared
     * until we can migrate doctrine/common and keep BC
     * with PHP 5.6 and Symfony 3.4, we allow deprecation
     * notice for this test.
     *
     * https://github.com/doctrine/common/issues/826
     *
     * @group legacy
     */
    public function testIndexing(): void
    {
        $searchablePost = $this->createSearchablePost();

        // Delete index in case there is already something
        $this->engine->delete($searchablePost->getIndexName(), []);

        // Index
        $result = $this->engine->index($searchablePost, [
            'autoGenerateObjectIDIfNotExist' => true,
        ]);
        self::assertArrayHasKey($searchablePost->getIndexName(), $result);
        self::assertEquals(1, $result[$searchablePost->getIndexName()]->count());

        // Remove
        $result = $this->engine->remove($searchablePost, [
            'X-Forwarded-For' => '0.0.0.0',
        ]);
        self::assertArrayHasKey($searchablePost->getIndexName(), $result);
        self::assertEquals(1, $result[$searchablePost->getIndexName()]->count());

        // Update
        $result = $this->engine->index($searchablePost, [
            'createIfNotExists' => true,
        ]);
        self::assertArrayHasKey($searchablePost->getIndexName(), $result);
        self::assertEquals(1, $result[$searchablePost->getIndexName()]->count());
        foreach ($result as $indexName => $response) {
            $response->wait();
        }

        // Search
        $result = $this->engine->search('Test', $searchablePost->getIndexName(), [
            'page'                 => 0,
            'hitsPerPage'          => 20,
            'attributesToRetrieve' => [
                'title',
            ],
        ]);
        self::assertArrayHasKey('hits', $result);
        self::assertArrayHasKey('nbHits', $result);
        self::assertArrayHasKey('page', $result);
        self::assertArrayHasKey('title', $result['hits'][0]);
        self::assertArrayNotHasKey('content', $result['hits'][0]);

        // Search IDs
        $result = $this->engine->searchIds('This should not have results', $searchablePost->getIndexName(), [
            'page'                 => 1,
            'hitsPerPage'          => 20,
            'attributesToRetrieve' => [
                'title',
            ],
        ]);
        self::assertEmpty($result);

        // Count
        $result = $this->engine->count('', $searchablePost->getIndexName(), ['tagFilters' => 'test']);
        self::assertEquals(0, $result);
        $result = $this->engine->count('This should not have results', $searchablePost->getIndexName(), []);
        self::assertEquals(0, $result);

        // Cleanup
        $result = $this->engine->clear($searchablePost->getIndexName(), []);
        self::assertInstanceOf(IndexingResponse::class, $result);

        // Delete index
        $result = $this->engine->delete($searchablePost->getIndexName(), []);
        self::assertInstanceOf(IndexingResponse::class, $result);
    }

    public function testIndexingEmptyEntity(): void
    {
        $searchableImage = $this->createSearchableImage();
        $requestOptions  = [];

        // Delete index in case there is already something
        $this->engine->delete($searchableImage->getIndexName(), $requestOptions);

        // Index
        $result = $this->engine->index($searchableImage, $requestOptions);
        self::assertEmpty($result);

        // Remove
        $result = $this->engine->remove($searchableImage, $requestOptions);
        self::assertEmpty($result);

        // Update
        $result = $this->engine->index($searchableImage, $requestOptions);
        self::assertEmpty($result);

        // Search
        try {
            $this->engine->search('query', $searchableImage->getIndexName(), $requestOptions);
        } catch (\Exception $e) {
            self::assertInstanceOf('Algolia\AlgoliaSearch\Exceptions\NotFoundException', $e);
        }
    }
}
