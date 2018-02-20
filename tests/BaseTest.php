<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Doctrine\NullConnection;
use Algolia\SearchBundle\Engine\AlgoliaEngine;
use Algolia\SearchBundle\Engine\AlgoliaSyncEngine;
use Algolia\SearchBundle\Engine\NullEngine;
use Algolia\SearchBundle\Entity\Comment;
use AlgoliaSearch\Client;
use Algolia\SearchBundle\Entity\Post;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    protected $container;

    public function setUp()
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        $this->container = $kernel->getContainer();
    }

    protected function createIndexManager($engine = null, $config = 'default')
    {
        $config = $this->getConfig();

        $engine = $this->createEngine($engine);

        $indexManager = new IndexManager($this->container->get('serializer'), $engine, $config);

        return $indexManager;
    }

    protected function createPost($id = null)
    {
        $post = new Post;
        $post->setTitle('Test');
        $post->setSlug('test');
        $post->setContent('Test content');

        if (!is_null($id)) {
            $post->setSlug('test-'.$id);
            $post->setId($id);
        }

        return $post;
    }

    protected function createSearchablePost()
    {
        $config = $this->getConfig();
        $post = $this->createPost(rand(100, 300));
        $om = $this->getObjectManager();

        return new SearchableEntity(
            $config['prefix'].'posts',
            $post,
            $om->getClassMetadata(Post::class),
            $this->container->get('serializer')
        );
    }

    protected function createComment($id = null)
    {
        $comment = new Comment;
        $comment->setContent('Comment content');

        if (!is_null($id)) {
            $comment->setId($id);
        }

        return $comment;
    }

    protected function getObjectManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @param $engine
     * @return AlgoliaEngine|AlgoliaSyncEngine|NullEngine
     */
    protected function createEngine($engine)
    {
        if ('algolia' == $engine) {
            $engine = new AlgoliaEngine(new Client(
                getenv('ALGOLIA_APP_ID'),
                getenv('ALGOLIA_API_KEY')
            ));
        } elseif ('algolia-sync' == $engine) {
            $engine = new AlgoliaSyncEngine(new Client(
                getenv('ALGOLIA_APP_ID'),
                getenv('ALGOLIA_API_KEY')
            ));
        } else {
            $engine = new NullEngine;
        }

        return $engine;
    }

    protected function getConfig($config = 'default')
    {
        return require __DIR__.'/config/'.$config.'.php';
    }
}
