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
    /** @var \Algolia\SearchBundle\IndexManager */
    protected $indexManager;

    public function setUp()
    {
        parent::setUp();

        $application = new Application(self::$kernel);
        $this->refreshDb($application);
        $this->indexManager = $this->get('search.index_manager');
    }

    public function tearDown()
    {
        $this->indexManager->delete(Post::class);
        $this->indexManager->delete(Comment::class);
        $this->indexManager->delete(Tag::class);
    }

    public function testDoctrineEventManagement()
    {
        $em = $this->get('doctrine')->getManager();
        for ($i = 0; $i < 5; $i++) {
            $post = $this->createPost();
            $em->persist($post);
        }
        $em->flush();
        sleep(2);

        $count = $this->indexManager->count('', Post::class);
        $this->assertEquals(5, $count);

        $raw = $this->indexManager->rawSearch('', Post::class);
        $this->assertArrayHasKey('query', $raw);
        $this->assertArrayHasKey('nbHits', $raw);
        $this->assertArrayHasKey('page', $raw);
        $this->assertTrue(is_array($raw['hits']));

        $posts = $this->indexManager->search('', Post::class, $em);
        $this->assertTrue(is_array($posts));
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $posts = $this->indexManager->search('', ContentAggregator::class, $em);
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $postToUpdate = $posts[4];
        $postToUpdate->setTitle('New Title');
        $em->flush();
        $posts = $this->indexManager->search('', ContentAggregator::class, $em);
        $this->assertEquals($posts[4]->getTitle(), 'New Title');

        $em->remove($posts[0]);
        sleep(2);
        $this->assertEquals(4, $this->indexManager->count('', Post::class));
    }

    public function testIndexIfFeature()
    {
        $tags = [
            new Tag(['id' => 1, 'name' => 'Tag #1']),
            new Tag(['id' => 2, 'name' => 'Tag #2']),
            new Tag(['id' => rand(10, 42), 'public' => false]),
        ];
        $em = $this->get('doctrine')->getManager();

        $result = $this->indexManager->index($tags, $em);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        $this->assertEquals(2, $this->indexManager->count('', Tag::class));

        $result = $this->indexManager->index($tags[2]->setPublic(true), $em);
        foreach ($result as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $apiResponse->wait();
            }
        }

        $this->assertEquals(3, $this->indexManager->count('', Tag::class));
    }
}
