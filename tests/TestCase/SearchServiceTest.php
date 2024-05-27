<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Tag;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Image;
use Algolia\SearchBundle\TestApp\Entity\Link;
use Algolia\SearchBundle\TestApp\Entity\Post;

class SearchServiceTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\Services\AlgoliaSearchService */
    protected $searchService;
    protected $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->searchService  = $this->get('search.service');
        $this->entityManager  = $this->get('doctrine')->getManager();
    }

    public function cleanUp(): void
    {
        $this->searchService->delete(Post::class)->wait();
        $this->searchService->delete(Comment::class)->wait();
        $this->searchService->delete(ContentAggregator::class)->wait();
    }

    public function testIsSearchableMethod(): void
    {
        self::assertTrue($this->searchService->isSearchable(Post::class));
        self::assertTrue($this->searchService->isSearchable(Comment::class));
        self::assertFalse($this->searchService->isSearchable(BaseTest::class));
        self::assertFalse($this->searchService->isSearchable(Image::class));
        self::assertTrue($this->searchService->isSearchable(ContentAggregator::class));
        self::assertTrue($this->searchService->isSearchable(Tag::class));
        self::assertTrue($this->searchService->isSearchable(Link::class));
        $this->cleanUp();
    }

    public function testGetSearchableEntities(): void
    {
        $result = $this->searchService->getSearchables();
        self::assertEquals([
            Post::class,
            Comment::class,
            ContentAggregator::class,
            Tag::class,
            Link::class,
        ], $result);
        $this->cleanUp();
    }

    public function testExceptionIfNoId(): void
    {
        $this->expectException(\Exception::class);
        $this->entityManager = $this->get('doctrine')->getManager();

        $this->searchService->index($this->entityManager, new Post());
        $this->cleanUp();
    }

    public function testIndexedDataAreSearchable(): void
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        // index Data
        $this->searchService->index($this->entityManager, $this->createPost(10));
        $this->searchService->index(
            $this->entityManager,
            array_merge(
                $posts,
                [$this->createComment(1), $this->createImage(1)]
            )
        )->wait();

        // RawSearch
        $searchPost = $this->searchService->rawSearch(Post::class);
        self::assertCount(4, $searchPost['hits']);
        $searchPost = $this->searchService->rawSearch(Post::class, '', [
            'page'        => 0,
            'hitsPerPage' => 1,
        ]);
        self::assertCount(1, $searchPost['hits']);

        $searchPostEmpty = $this->searchService->rawSearch(Post::class, 'with no result');
        self::assertCount(0, $searchPostEmpty['hits']);

        $searchComment = $this->searchService->rawSearch(Comment::class);
        self::assertCount(1, $searchComment['hits']);

        $searchPost = $this->searchService->rawSearch(ContentAggregator::class, 'test');
        self::assertCount(4, $searchPost['hits']);

        $searchPost = $this->searchService->rawSearch(ContentAggregator::class, 'Comment content');
        self::assertCount(1, $searchPost['hits']);

        // Count
        self::assertEquals(4, $this->searchService->count(Post::class, 'test'));
        self::assertEquals(1, $this->searchService->count(Comment::class, 'content'));
        self::assertEquals(6, $this->searchService->count(ContentAggregator::class));

        // Cleanup
        $this->searchService->delete(Post::class);
        $this->searchService->delete(Comment::class);
        $this->searchService->delete(ContentAggregator::class);
        $this->cleanUp();
    }

    public function testIndexedDataCanBeRemoved(): void
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $comment = $this->createComment(1);
        $image   = $this->createImage(1);

        // index Data
        $this->searchService->index(
            $this->entityManager,
            array_merge($posts, [$comment, $image])
        )->wait();

        // Remove the last post.
        $this->searchService->remove($this->entityManager, end($posts))->wait();

        // Expects 2 posts and 1 comment.
        self::assertEquals(2, $this->searchService->count(Post::class));
        self::assertEquals(1, $this->searchService->count(Comment::class));

        // The content aggregator expects 2 + 1 + 1.
        self::assertEquals(4, $this->searchService->count(ContentAggregator::class));

        // Remove the only comment that exists.
        $this->searchService->remove($this->entityManager, $comment)->wait();

        // Expects 2 posts and 0 comments.
        self::assertEquals(2, $this->searchService->count(Post::class));
        self::assertEquals(0, $this->searchService->count(Comment::class));

        // The content aggregator expects 2 + 0 + 1.
        self::assertEquals(3, $this->searchService->count(ContentAggregator::class));

        // Remove the only image that exists.
        $this->searchService->remove($this->entityManager, $image)->wait();

        // The content aggregator expects 2 + 0 + 0.
        self::assertEquals(2, $this->searchService->count(ContentAggregator::class));
        $this->cleanUp();
    }

    public function testRawSearchRawContent(): void
    {
        $postIndexed = $this->createPost(10);
        $postIndexed->setTitle('Foo Bar');

        $this->searchService->index($this->entityManager, $postIndexed)->wait();

        // Using entity.
        $results = $this->searchService->rawSearch(Post::class, 'Foo Bar');
        self::assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());

        // Using aggregator.
        $results = $this->searchService->rawSearch(ContentAggregator::class, 'Foo Bar');
        self::assertEquals($results['hits'][0]['title'], $postIndexed->getTitle());
        $this->cleanUp();
    }

    public function testIndexIfCondition(): void
    {
        $posts = [];
        for ($i = 0; $i < 3; $i++) {
            $posts[] = $this->createPost($i);
        }

        $post = $this->createPost(10);
        $post->setTitle('Foo');

        $posts[] = $post;

        // index Data: Total 4 posts.
        $this->searchService->index($this->entityManager, $posts)->wait();

        // The content aggregator expects 3 ( not 4, because of the index_if condition ).
        self::assertEquals(3, $this->searchService->count(ContentAggregator::class));
        $this->cleanUp();
    }

    public function testClearUnsearchableEntity(): void
    {
        $this->expectException(\Exception::class);
        $image = $this->createSearchableImage();

        $this->searchService->index($this->entityManager, [$image]);
        $this->searchService->clear(Image::class);
        $this->cleanUp();
    }

    public function testShouldNotBeIndexed(): void
    {
        $link = new Link();
        self::assertFalse($this->searchService->shouldBeIndexed($link));
        $this->cleanUp();
    }
}
