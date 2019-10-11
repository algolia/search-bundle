<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Algolia\SearchBundle\TestApp\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class DoctrineTest extends BaseTest
{
    /** @var \Algolia\SearchBundle\SearchService */
    protected $searchService;

    public function setUp()
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

    public function tearDown()
    {
        $this->searchService->delete(Post::class)->wait();
        $this->searchService->delete(Comment::class)->wait();
        $this->searchService->delete(Tag::class)->wait();
    }

    public function testDoctrineEventManagement()
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

        $this->assertEquals($expectedCount, $count);

        $raw = $this->searchService->rawSearch(Post::class);
        $this->assertArrayHasKey('query', $raw);
        $this->assertArrayHasKey('nbHits', $raw);
        $this->assertArrayHasKey('page', $raw);
        $this->assertTrue(is_array($raw['hits']));

        $posts = $this->searchService->search($em, Post::class);
        $this->assertTrue(is_array($posts));
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $posts = $this->searchService->search($em, ContentAggregator::class);
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $postToUpdate = $posts[4];
        $postToUpdate->setTitle('New Title');
        $em->flush();
        $posts = $this->searchService->search($em, ContentAggregator::class);
        $this->assertEquals($posts[4]->getTitle(), 'New Title');

        $em->remove($posts[0]);

        $iteration     = 0;
        $expectedCount = 4;
        do {
            $count = $this->searchService->count(Post::class);
            sleep(1);
            $iteration++;
        } while ($count !== $expectedCount || $iteration === 10);

        $this->assertEquals($count, $expectedCount);
    }

    public function testIndexIfFeature()
    {
        $tags = [
            new Tag(['id' => 1, 'name' => 'Tag #1']),
            new Tag(['id' => 2, 'name' => 'Tag #2']),
            new Tag(['id' => rand(10, 42), 'name' => 'Tag #3', 'public' => false]),
        ];
        $em = $this->get('doctrine')->getManager();

        $this->searchService->index($em, $tags)->wait();

        $this->assertEquals(2, $this->searchService->count(Tag::class));

        $this->searchService->index($em, $tags[2]->setPublic(true))->wait();

        $this->assertEquals(3, $this->searchService->count(Tag::class));
    }
}
