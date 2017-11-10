<?php

namespace Algolia\SearchableBundle;


use Algolia\SearchableBundle\Doctrine\NullObjectManager;
use Algolia\SearchableBundle\Entity\Comment;
use Algolia\SearchableBundle\Entity\Post;
use Algolia\SearchableBundle\Entity\Tag;

class IndexManagerTest extends BaseTest
{
    public function testIsSearchableMethod()
    {
        $indexManager = $this->createIndexManager();

        $this->assertTrue($indexManager->isSearchable(Post::class));
        $this->assertTrue($indexManager->isSearchable(Comment::class));

        $this->assertFalse($indexManager->isSearchable(Tag::class));
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionIfNoId()
    {
        $om = $this->getObjectManager();
        $indexManager = $this->createIndexManager();

        $indexManager->index(new Post, $om);
    }

    public function testNonPersistedDataAreIndexed()
    {
        $om = $this->getObjectManager();
        $indexManager = $this->createIndexManager('algolia-sync');

        $posts = [];
        for ($i=0; $i<3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $indexManager->index($this->createPost(10), $om);
        $indexManager->index(array_merge($posts, [$this->createComment(1)]), $om);

        // RawSearch
        $searchPost = $indexManager->rawSearch('', Post::class);
        $this->assertCount(4, $searchPost['hits']);
        $searchPost = $indexManager->rawSearch('', Post::class, 1, 1);
        $this->assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $indexManager->rawSearch('with no result', Post::class);
        $this->assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $indexManager->rawSearch('', Comment::class);
        $this->assertCount(1, $searchComment['hits']);

        // Count
        $this->assertEquals(4, $indexManager->count('test', Post::class));
        $this->assertEquals(1, $indexManager->count('content', Comment::class));

        // Cleanup
        $indexManager->delete(Post::class);
        $indexManager->delete(Comment::class);
    }
}
