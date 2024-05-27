<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Tag;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class DoctrineTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\Services\AlgoliaSearchService */
    protected $searchService;

    public function setUp(): void
    {
        parent::setUp();

        $application = new Application(self::$kernel);
        $this->refreshDb($application);
        $this->searchService = $this->get('search.service');

        $client        = $this->get('search.client');
        $indexName     = 'posts';
        $index         = $client->initIndex($this->getPrefix() . $indexName);
        $index->setSettings($this->getDefaultConfig())->wait();
    }

    public function cleanUp(): void
    {
        $this->searchService->delete(Post::class)->wait();
        $this->searchService->delete(Comment::class)->wait();
        $this->searchService->delete(Tag::class)->wait();
    }

    public function testDoctrineEventManagement(): void
    {
        $em = $this->get('doctrine')->getManager();
        for ($i = 0; $i < 5; $i++) {
            $post = $this->createPost();
            $em->persist($post);
        }
        $em->flush();

        $iteration     = 0;
        $expectedCount = 5;
        do {
            $count = $this->searchService->count(Post::class);
            sleep(1);
            $iteration++;
        } while ($count !== $expectedCount || $iteration === 10);

        self::assertEquals($expectedCount, $count);

        $raw = $this->searchService->rawSearch(Post::class);
        self::assertArrayHasKey('query', $raw);
        self::assertArrayHasKey('nbHits', $raw);
        self::assertArrayHasKey('page', $raw);
        self::assertIsArray($raw['hits']);

        $posts = $this->searchService->search($em, Post::class);
        self::assertIsArray($posts);
        foreach ($posts as $p) {
            self::assertInstanceOf(Post::class, $p);
        }

        $posts = $this->searchService->search($em, ContentAggregator::class);
        foreach ($posts as $p) {
            self::assertInstanceOf(Post::class, $p);
        }

        $postToUpdate = $posts[4];
        $postToUpdate->setTitle('New Title');
        $em->flush();
        $posts = $this->searchService->search($em, ContentAggregator::class);
        self::assertEquals('New Title', $posts[4]->getTitle());

        $em->remove($posts[0]);

        $iteration     = 0;
        $expectedCount = 4;
        do {
            $count = $this->searchService->count(Post::class);
            sleep(1);
            $iteration++;
        } while ($count !== $expectedCount || $iteration === 10);

        self::assertEquals($count, $expectedCount);
        $this->cleanUp();
    }

    public function testIndexIfFeature(): void
    {
        $tags = [
            new Tag(['id' => 1, 'name' => 'Tag #1']),
            new Tag(['id' => 2, 'name' => 'Tag #2']),
            new Tag(['id' => random_int(10, 42), 'name' => 'Tag #3', 'public' => false]),
        ];
        $em = $this->get('doctrine')->getManager();

        $this->searchService->index($em, $tags)->wait();

        self::assertEquals(2, $this->searchService->count(Tag::class));

        $this->searchService->index($em, $tags[2]->setPublic(true))->wait();

        self::assertEquals(3, $this->searchService->count(Tag::class));
        $this->cleanUp();
    }
}
