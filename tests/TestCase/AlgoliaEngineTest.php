<?php

namespace Algolia\SearchBundle;

class AlgoliaEngineTest extends BaseTest
{
    /**
     *
     * Doctrine is currently splitting the common package
     * into 3 separate ones, some deprecation notice appeared
     * until we can migrate doctrine/common and keep BC
     * with PHP 5.6 and Symfony 3.4, we allow deprecation
     * notice for this test
     *
     * https://github.com/doctrine/common/issues/826
     *
     * @group legacy
     *
     */
    public function testIndexing()
    {
        $engine = $this->get('search.engine');

        $searchablePost = $this->createSearchablePost();
        // Delete index in case there is already something
        $engine->delete($searchablePost->getIndexName());

        // Indexing
        $result = $engine->add($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        $result = $engine->remove($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        $result = $engine->update($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertEquals(1, $result[$searchablePost->getIndexName()]);

        // Search
        $result = $engine->search('query', $searchablePost->getIndexName());
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('nbHits', $result);
        $this->assertArrayHasKey('page', $result);

        $result = $engine->searchIds('This should not have results', $searchablePost->getIndexName());
        $this->assertEmpty($result);

        $result = $engine->count('', $searchablePost->getIndexName());
        $this->assertEquals(1, $result);
        $result = $engine->count('This should not have results', $searchablePost->getIndexName());
        $this->assertEquals(0, $result);
        $result = $engine->count('', $searchablePost->getIndexName(), ['tagFilters' => 'test']);
        $this->assertEquals(0, $result);

        // Cleanup
        $result = $engine->clear($searchablePost->getIndexName());
        $this->assertTrue($result);

        // Delete index
        $result = $engine->delete($searchablePost->getIndexName());
        $this->assertTrue($result);
    }
}
