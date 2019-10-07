<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Algolia\SearchBundle\TestApp\Entity\Image;
use Algolia\SearchBundle\TestApp\Entity\Tag;
use Algolia\SearchBundle\TestApp\Entity\Link;

class SearchServiceTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\SearchService */
    protected $searchService;
    protected $entityManager;

    public function setUp()
    {
        parent::setUp();
        $this->searchService  = $this->get('search.service');
        $this->entityManager  = $this->get('doctrine')->getManager();
    }

    public function tearDown()
    {
        $this->searchService->delete(Post::class)->wait();
        $this->searchService->delete(Comment::class)->wait();
        $this->searchService->delete(ContentAggregator::class)->wait();
    }

    public function testIsSearchableMethod()
    {
        $this->assertTrue($this->searchService->isSearchable(Post::class));
        $this->assertTrue($this->searchService->isSearchable(Comment::class));
        $this->assertFalse($this->searchService->isSearchable(BaseTest::class));
        $this->assertFalse($this->searchService->isSearchable(Image::class));
        $this->assertTrue($this->searchService->isSearchable(ContentAggregator::class));
        $this->assertTrue($this->searchService->isSearchable(Tag::class));
        $this->assertTrue($this->searchService->isSearchable(Link::class));
    }

    public function testGetSearchableEntities()
    {
        $result = $this->searchService->getSearchableEntities();
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

        $this->searchService->index(new Post(), $this->entityManager);
    }

    public function testIndexedDataAreSearchable()
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $this->searchService->index($this->createPost(10), $this->entityManager);
        $this->searchService->index(
            array_merge(
                $posts,
                [$this->createComment(1), $this->createImage(1)]
            ),
            $this->entityManager
        )->wait();

        // RawSearch
        $searchPost = $this->searchService->rawSearch('', Post::class);
        $this->assertCount(4, $searchPost['hits']);
        $searchPost = $this->searchService->rawSearch('', Post::class, [
            'page'        => 0,
            'hitsPerPage' => 1,
        ]);
        $this->assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $this->searchService->rawSearch('with no result', Post::class);
        $this->assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $this->searchService->rawSearch('', Comment::class);
        $this->assertCount(1, $searchComment['hits']);

        $searchPost = $this->searchService->rawSearch('test', ContentAggregator::class);
        $this->assertCount(4, $searchPost['hits']);

        $searchPost = $this->searchService->rawSearch('Comment content', ContentAggregator::class);
        $this->assertCount(1, $searchPost['hits']);

        // Count
        $this->assertEquals(4, $this->searchService->count('test', Post::class));
        $this->assertEquals(1, $this->searchService->count('content', Comment::class));
        $this->assertEquals(6, $this->searchService->count('', ContentAggregator::class));

        // Cleanup
        $this->searchService->delete(Post::class);
        $this->searchService->delete(Comment::class);
        $this->searchService->delete(ContentAggregator::class);
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
        $this->searchService->index(
            array_merge($posts, [$comment, $image]),
            $this->entityManager
        )->wait();

        // Remove the last post.
        $this->searchService->remove(end($posts), $this->entityManager)->wait();

        // Expects 2 posts and 1 comment.
        $this->assertEquals(2, $this->searchService->count('', Post::class));
        $this->assertEquals(1, $this->searchService->count('', Comment::class));

        // The content aggregator expects 2 + 1 + 1.
        $this->assertEquals(4, $this->searchService->count('', ContentAggregator::class));

        // Remove the only comment that exists.
        $this->searchService->remove($comment, $this->entityManager)->wait();

        // Expects 2 posts and 0 comments.
        $this->assertEquals(2, $this->searchService->count('', Post::class));
        $this->assertEquals(0, $this->searchService->count('', Comment::class));

        // The content aggregator expects 2 + 0 + 1.
        $this->assertEquals(3, $this->searchService->count('', ContentAggregator::class));

        // Remove the only image that exists.
        $this->searchService->remove($image, $this->entityManager)->wait();

        // The content aggregator expects 2 + 0 + 0.
        $this->assertEquals(2, $this->searchService->count('', ContentAggregator::class));
    }

    public function testRawSearchRawContent()
    {
        $postIndexed = $this->createPost(10);
        $postIndexed->setTitle('Foo Bar');

        $this->searchService->index($postIndexed, $this->entityManager)->wait();

        // Using entity.
        $results = $this->searchService->rawSearch('Foo Bar', Post::class);
        $this->assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());

        // Using aggregator.
        $results = $this->searchService->rawSearch('Foo Bar', ContentAggregator::class);
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
        $this->searchService->index($posts, $this->entityManager)->wait();

        // The content aggregator expects 3 ( not 4, because of the index_if condition ).
        $this->assertEquals(3, $this->searchService->count('', ContentAggregator::class));
    }

    /**
     * @expectedException \Exception
     */
    public function testClearUnsearchableEntity()
    {
        $image = $this->createSearchableImage();

        $this->searchService->index([$image], $this->entityManager);
        $this->searchService->clear(Image::class);
    }

    public function testShouldNotBeIndexed()
    {
        $link = new Link();
        $this->assertFalse($this->searchService->shouldBeIndexed($link));
    }
}
