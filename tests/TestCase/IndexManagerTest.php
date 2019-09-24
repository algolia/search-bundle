<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Algolia\SearchBundle\TestApp\Entity\Image;
use Algolia\SearchBundle\TestApp\Entity\Tag;
use Algolia\SearchBundle\TestApp\Entity\Link;

class IndexManagerTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\IndexManager */
    protected $indexManager;
    protected $entityManager;

    public function setUp()
    {
        parent::setUp();
        $this->indexManager  = $this->get('search.index_manager');
        $this->entityManager = $this->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
        $this->indexManager->delete(ContentAggregator::class);
    }

    public function testIsSearchableMethod()
    {
        $this->assertTrue($this->indexManager->isSearchable(Post::class));
        $this->assertTrue($this->indexManager->isSearchable(Comment::class));
        $this->assertFalse($this->indexManager->isSearchable(BaseTest::class));
        $this->assertFalse($this->indexManager->isSearchable(Image::class));
        $this->assertTrue($this->indexManager->isSearchable(ContentAggregator::class));
        $this->assertTrue($this->indexManager->isSearchable(Tag::class));
        $this->assertTrue($this->indexManager->isSearchable(Link::class));
    }

    public function testGetSearchableEntities()
    {
        $result = $this->indexManager->getSearchableEntities();
        $this->assertEquals([
            Post::class,
            Comment::class,
            ContentAggregator::class,
            Tag::class,
            Link::class,
        ], $result);
    }

    /**
     * @expectedException \Exception
     */
    public function testExceptionIfNoId()
    {
        $this->entityManager = $this->get('doctrine')->getManager();

        $this->indexManager->index(new Post(), $this->entityManager);
    }

    public function testIndexedDataAreSearchable()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $this->indexManager->index($this->createPost(10), $this->entityManager);
        $result = $this->indexManager->index(array_merge($posts, [$this->createComment(1), $this->createImage(1)]), $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // RawSearch
        $searchPost = $this->indexManager->rawSearch('', Post::class);
        $this->assertCount(4, $searchPost['hits']);
        $searchPost = $this->indexManager->rawSearch('', Post::class, [
            'page'        => 0,
            'hitsPerPage' => 1,
        ]);
        $this->assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $this->indexManager->rawSearch('with no result', Post::class);
        $this->assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $this->indexManager->rawSearch('', Comment::class);
        $this->assertCount(1, $searchComment['hits']);

        $searchPost = $this->indexManager->rawSearch('test', ContentAggregator::class);
        $this->assertCount(4, $searchPost['hits']);

        $searchPost = $this->indexManager->rawSearch('Comment content', ContentAggregator::class);
        $this->assertCount(1, $searchPost['hits']);

        // Count
        $this->assertEquals(4, $this->indexManager->count('test', Post::class));
        $this->assertEquals(1, $this->indexManager->count('content', Comment::class));
        $this->assertEquals(6, $this->indexManager->count('', ContentAggregator::class));

        // Cleanup
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
        $this->indexManager->delete(ContentAggregator::class);
    }

    public function testIndexedDataCanBeRemoved()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $comment = $this->createComment(1);
        $image   = $this->createImage(1);

        // index Data
        $result = $this->indexManager->index(array_merge($posts, [$comment, $image]), $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // Remove the last post.
        $result = $this->indexManager->remove(end($posts), $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // Expects 2 posts and 1 comment.
        $this->assertEquals(2, $this->indexManager->count('', Post::class));
        $this->assertEquals(1, $this->indexManager->count('', Comment::class));

        // The content aggregator expects 2 + 1 + 1.
        $this->assertEquals(4, $this->indexManager->count('', ContentAggregator::class));

        // Remove the only comment that exists.
        $result = $this->indexManager->remove($comment, $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // Expects 2 posts and 0 comments.
        $this->assertEquals(2, $this->indexManager->count('', Post::class));
        $this->assertEquals(0, $this->indexManager->count('', Comment::class));

        // The content aggregator expects 2 + 0 + 1.
        $this->assertEquals(3, $this->indexManager->count('', ContentAggregator::class));

        // Remove the only image that exists.
        $result = $this->indexManager->remove($image, $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // The content aggregator expects 2 + 0 + 0.
        $this->assertEquals(2, $this->indexManager->count('', ContentAggregator::class));
    }

    public function testRawSearchRawContent()
    {
        $postIndexed = $this->createPost(10);
        $postIndexed->setTitle('Foo Bar');

        $result = $this->indexManager->index($postIndexed, $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // Using entity.
        $results = $this->indexManager->rawSearch('Foo Bar', Post::class);
        $this->assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());

        // Using aggregator.
        $results = $this->indexManager->rawSearch('Foo Bar', ContentAggregator::class);
        $this->assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());
    }

    public function testIndexIfCondition()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $post = $this->createPost(10);
        $post->setTitle('Foo');

        $posts[] = $post;

        // index Data: Total 4 posts.
        $result = $this->indexManager->index($posts, $this->entityManager);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        // The content aggregator expects 3 ( not 4, because of the index_if condition ).
        $this->assertEquals(3, $this->indexManager->count('', ContentAggregator::class));
    }

    /**
     * @expectedException \Exception
     */
    public function testClearUnsearchableEntity()
    {
        $image = $this->createSearchableImage();

        $this->indexManager->index([$image], $this->entityManager);
        $this->indexManager->clear(Image::class);
    }

    public function testShouldNotBeIndexed()
    {
        $link = new Link();
        $this->assertFalse($this->indexManager->shouldBeIndexed($link));
    }
}
