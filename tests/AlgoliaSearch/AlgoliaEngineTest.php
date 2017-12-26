<?php

namespace Algolia\SearchBundle;

class AlgoliaEngineTest extends BaseTest
{
    public function testIndexing()
    {
        $engine = $this->createEngine('algolia-sync');
        $searchablePost = $this->createSearchablePost();
        // Delete index in case there is already something
        $engine->delete($searchablePost->getIndexName());

        // Indexing
        $result = $engine->add($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertArrayHasKey('taskID', $result[$searchablePost->getIndexName()]);

        $result = $engine->remove($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertArrayHasKey('taskID', $result[$searchablePost->getIndexName()]);

        $result = $engine->update($searchablePost);
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertArrayHasKey('taskID', $result[$searchablePost->getIndexName()]);

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

        // Cleanup
        $result = $engine->clear($searchablePost->getIndexName());
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertArrayHasKey('taskID', $result[$searchablePost->getIndexName()]);

        // Delete index
        $result = $engine->delete($searchablePost->getIndexName());
        $this->assertArrayHasKey($searchablePost->getIndexName(), $result);
        $this->assertArrayHasKey('taskID', $result[$searchablePost->getIndexName()]);
    }
}
