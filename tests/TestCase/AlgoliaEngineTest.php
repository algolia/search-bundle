<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;

class AlgoliaEngineTest extends BaseTest
{
    protected $engine;

    public function setUp()
    {
        parent::setUp();
        $this->engine = $this->get('search.engine');
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
    public function testIndexing()
    {
        $searchablePost = $this->createSearchablePost();

        // Delete index in case there is already something
        $this->engine->delete($searchablePost->getIndexName());

        // Index
        $result = $this->engine->add($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Remove
        $result = $this->engine->remove($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Update
        $result = $this->engine->update($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Search
        $result = $this->engine->search('query', $searchablePost->getIndexName());
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('nbHits', $result);
        $this->assertArrayHasKey('page', $result);

        // Search IDs
        $result = $this->engine->searchIds('This should not have results', $searchablePost->getIndexName());
        $this->assertEmpty($result);

        // Count
        $result = $this->engine->count('', $searchablePost->getIndexName());
        $this->assertEquals(1, $result);
        $result = $this->engine->count('This should not have results', $searchablePost->getIndexName());
        $this->assertEquals(0, $result);

        // Cleanup
        $result = $this->engine->clear($searchablePost->getIndexName());
        $this->assertTrue($result);

        // Delete index
        $result = $this->engine->delete($searchablePost->getIndexName());
        $this->assertTrue($result);
    }

    public function testIndexingWithRequestOptions()
    {
        $searchablePost = $this->createSearchablePost();

        // Delete index in case there is already something
        $this->engine->delete($searchablePost->getIndexName());

        // Index
        $result = $this->engine->add($searchablePost, [
            'autoGenerateObjectIDIfNotExist' => true,
        ]);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Remove
        $result = $this->engine->remove($searchablePost, [
            'X-Forwarded-For' => '0.0.0.0',
        ]);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Update
        $result = $this->engine->update($searchablePost, [
            'createIfNotExists' => true,
        ]);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Search
        $result = $this->engine->search('Test', $searchablePost->getIndexName(), [
            'page'                 => 0,
            'hitsPerPage'          => 20,
            'attributesToRetrieve' => [
                'title',
            ],
        ]);
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('nbHits', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('title', $result['hits'][0]);
        $this->assertArrayNotHasKey('content', $result['hits'][0]);

        // Search IDs
        $result = $this->engine->searchIds('This should not have results', $searchablePost->getIndexName(), [
            'page'                 => 1,
            'hitsPerPage'          => 20,
            'attributesToRetrieve' => [
                'title',
            ],
        ]);
        $this->assertEmpty($result);

        // Count
        $result = $this->engine->count('', $searchablePost->getIndexName(), ['tagFilters' => 'test']);
        $this->assertEquals(0, $result);

        $this->engine->delete($searchablePost->getIndexName());
    }
}
