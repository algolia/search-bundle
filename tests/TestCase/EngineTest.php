<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\AlgoliaSearch\RequestOptions\RequestOptions;
use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Engine;
use Algolia\SearchBundle\Responses\NullResponse;

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
        self::assertCount(1, $result[$searchablePost->getIndexName()][0]['objectIDs']);

        // Remove
        $result = $this->engine->remove($searchablePost, [
            'X-Forwarded-For' => '0.0.0.0',
        ]);
        self::assertArrayHasKey($searchablePost->getIndexName(), $result);
        self::assertCount(1, $result[$searchablePost->getIndexName()][0]['objectIDs']);

        // Update
        $result = $this->engine->index($searchablePost, [
            'createIfNotExists' => true,
        ]);
        self::assertArrayHasKey($searchablePost->getIndexName(), $result);
        self::assertCount(1, $result[$searchablePost->getIndexName()][0]['objectIDs']);
        foreach ($result as $indexName => $responses) {
            foreach ($responses as $response) {
                $this->get('search.client')->waitForTask($indexName, $response['taskID']);
            }
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
        self::assertNotInstanceOf(NullResponse::class, $result);

        // Delete index
        $result = $this->engine->delete($searchablePost->getIndexName(), []);
        self::assertNotInstanceOf(NullResponse::class, $result);
    }

    /**
     * Same as testIndexing but passes RequestOptions objects instead of arrays.
     * Verifies that RequestOptions work identically to plain arrays across
     * all Engine methods (index, remove, search, searchIds, count, clear, delete).
     *
     * @group legacy
     */
    public function testIndexingWithRequestOptions(): void
    {
        $searchablePost = $this->createSearchablePost();
        $indexName      = $searchablePost->getIndexName();

        // Delete index in case there is already something
        $this->engine->delete($indexName, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));

        // Index
        $result = $this->engine->index($searchablePost, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertArrayHasKey($indexName, $result);
        self::assertCount(1, $result[$indexName][0]['objectIDs']);

        // Remove
        $result = $this->engine->remove($searchablePost, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertArrayHasKey($indexName, $result);
        self::assertCount(1, $result[$indexName][0]['objectIDs']);

        // Re-index for search tests
        $result = $this->engine->index($searchablePost, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertArrayHasKey($indexName, $result);
        foreach ($result as $name => $responses) {
            foreach ($responses as $response) {
                $this->get('search.client')->waitForTask($name, $response['taskID']);
            }
        }

        // Search — verify search params in body are forwarded correctly
        $result = $this->engine->search('Test', $indexName, new RequestOptions([
            'headers'      => [], 'queryParameters' => [], 'readTimeout' => 30,
            'writeTimeout' => 30, 'connectTimeout' => 5,
            'body'         => [
                'page'                 => 0,
                'hitsPerPage'          => 1,
                'attributesToRetrieve' => ['title'],
            ],
        ]));
        self::assertArrayHasKey('hits', $result);
        self::assertCount(1, $result['hits']);
        self::assertEquals(0, $result['page']);
        self::assertEquals(1, $result['hitsPerPage']);
        self::assertArrayHasKey('title', $result['hits'][0]);
        self::assertArrayNotHasKey('content', $result['hits'][0]);

        // Search IDs
        $result = $this->engine->searchIds('This should not have results', $indexName, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => ['page' => 1, 'hitsPerPage' => 20],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertEmpty($result);

        // Count
        $result = $this->engine->count('Test', $indexName, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertEquals(1, $result);

        // Clear
        $result = $this->engine->clear($indexName, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertNotInstanceOf(NullResponse::class, $result);

        // Delete
        $result = $this->engine->delete($indexName, new RequestOptions([
            'headers'     => [], 'queryParameters' => [], 'body' => [],
            'readTimeout' => 30, 'writeTimeout' => 30, 'connectTimeout' => 5,
        ]));
        self::assertNotInstanceOf(NullResponse::class, $result);
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
