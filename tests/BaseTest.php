<?php

namespace Algolia\SearchableBundle;

use Algolia\SearchableBundle\Doctrine\NullConnection;
use Algolia\SearchableBundle\Engine\AlgoliaEngine;
use Algolia\SearchableBundle\Engine\AlgoliaSyncEngine;
use Algolia\SearchableBundle\Engine\NullEngine;
use Algolia\SearchableBundle\Entity\Comment;
use AlgoliaSearch\Client;
use Algolia\SearchableBundle\Entity\Post;
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

        $indexManager = new IndexManager($engine, $config['indices'], $config['prefix'], $config['nbResults']);

        return $indexManager;
    }

    protected function createPost($id = null)
    {
        $post = new Post;
        $post->setTitle('Test');
        $post->setContent('Test content');

        if (! is_null($id)) {
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
            $om->getClassMetadata(Post::class)
        );
    }

    protected function createComment($id = null)
    {
        $comment = new Comment;
        $comment->setContent('Comment content');

        if (! is_null($id)) {
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
                getenv('ALGOLIA_ID'),
                getenv('ALGOLIA_KEY')
            ));
        } elseif ('algolia-sync' == $engine) {
            $engine = new AlgoliaSyncEngine(new Client(
                getenv('ALGOLIA_ID'),
                getenv('ALGOLIA_KEY')
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
