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
    protected $syncIndexManager;

    public function setUp()
    {
        parent::setUp();

        $application = new Application(self::$kernel);
        $this->refreshDb($application);
        $this->syncIndexManager = $this->get('search.index_manager');
    }

    public function tearDown()
    {
//        $this->syncIndexManager->delete(Post::class);
        $this->syncIndexManager->delete(Comment::class);
        $this->syncIndexManager->delete(Tag::class);
    }

    public function testDoctrineEventManagement()
    {
        $em = $this->get('doctrine')->getManager();
        for ($i = 0; $i < 5; $i++) {
            $post = $this->createPost();
            $em->persist($post);
        }
        $em->flush();

        $count = $this->syncIndexManager->count('', Post::class);
        $this->assertEquals(5, $count);

        $raw = $this->syncIndexManager->rawSearch('', Post::class);
        $this->assertArrayHasKey('query', $raw);
        $this->assertArrayHasKey('nbHits', $raw);
        $this->assertArrayHasKey('page', $raw);
        $this->assertTrue(is_array($raw['hits']));

        $posts = $this->syncIndexManager->search('', Post::class, $em);
        $this->assertTrue(is_array($posts));
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $posts = $this->syncIndexManager->search('', ContentAggregator::class, $em);
        foreach ($posts as $p) {
            $this->assertInstanceOf(Post::class, $p);
        }

        $postToUpdate = $posts[4];
        $postToUpdate->setTitle('New Title');
        $em->flush();
        $posts = $this->syncIndexManager->search('', ContentAggregator::class, $em);
        $this->assertEquals($posts[4]->getTitle(), 'New Title');

        $em->remove($posts[0]);
        $this->assertEquals(4, $this->syncIndexManager->count('', Post::class));
    }

    public function testIndexIfFeature()
    {
        $tags = [
            new Tag(['id' => 1, 'name' => 'Tag #1']),
            new Tag(['id' => 2, 'name' => 'Tag #2']),
            new Tag(['id' => rand(10, 42), 'public' => false]),
        ];
        $em = $this->get('doctrine')->getManager();

        $this->syncIndexManager->index($tags, $em);
        $this->assertEquals(2, $this->syncIndexManager->count('', Tag::class));

        $this->syncIndexManager->index($tags[2]->setPublic(true), $em);
        $this->assertEquals(3, $this->syncIndexManager->count('', Tag::class));
    }
}
