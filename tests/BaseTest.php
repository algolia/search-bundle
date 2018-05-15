<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Doctrine\NullConnection;
use Algolia\SearchBundle\Engine\AlgoliaEngine;
use Algolia\SearchBundle\Engine\AlgoliaSyncEngine;
use Algolia\SearchBundle\Engine\NullEngine;
use Algolia\SearchBundle\Entity\Comment;
use AlgoliaSearch\Client;
use Algolia\SearchBundle\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Yaml\Yaml;

class BaseTest extends KernelTestCase
{
    public function setUp()
    {
        $this->bootKernel();
    }

    protected function createPost($id = null)
    {
        $post = new Post;
        $post->setTitle('Test');
        $post->setContent('Test content');

        if (!is_null($id)) {
            $post->setId($id);
        }

        return $post;
    }

    protected function createSearchablePost()
    {
        $post = $this->createPost(rand(100, 300));

        return new SearchableEntity(
            $this->getPrefix().'posts',
            $post,
            $this->get('doctrine')->getManager()->getClassMetadata(Post::class),
            $this->get('serializer')
        );
    }

    protected function createComment($id = null)
    {
        $comment = new Comment;
        $comment->setContent('Comment content');
        $comment->setPost(new Post(['title' => 'What a post!']));

        if (!is_null($id)) {
            $comment->setId($id);
        }

        return $comment;
    }

    protected function getPrefix()
    {
        return getenv('ALGOLIA_PREFIX');
    }

    protected function get($id)
    {
        return self::$kernel->getContainer()->get($id);
    }
}
