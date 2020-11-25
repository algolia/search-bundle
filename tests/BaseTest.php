<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\Image;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class BaseTest extends KernelTestCase
{
    public static function setUpBeforeClass()
    {
        /*
         * Older version of PHPUnit (<6.0) load
         * env variables differently, we override them
         * here to make sure they're coming from the
         * env rather than the XML config
         */
        if (class_exists('\PHPUnit_Runner_Version')) {
            $_ENV['ALGOLIA_PREFIX']    = getenv('ALGOLIA_PREFIX');
            $_ENV['CIRCLE_BUILD_NUM']  = getenv('CIRCLE_BUILD_NUM');
        }
    }

    public function setUp()
    {
        $this->bootKernel();
    }

    protected function createPost($id = null)
    {
        $post = new Post();
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
            $this->getPrefix() . 'posts',
            $post,
            $this->get('doctrine')->getManager()->getClassMetadata(Post::class),
            $this->get('serializer')
        );
    }

    protected function createComment($id = null)
    {
        $comment = new Comment();
        $comment->setContent('Comment content');
        $comment->setPost(new Post(['title' => 'What a post!']));

        if (!is_null($id)) {
            $comment->setId($id);
        }

        return $comment;
    }

    protected function createImage($id = null)
    {
        $image = new Image();

        if (!is_null($id)) {
            $image->setId($id);
        }

        return $image;
    }

    protected function createSearchableImage()
    {
        $image = $this->createImage(rand(100, 300));

        return new SearchableEntity(
            $this->getPrefix() . 'image',
            $image,
            $this->get('doctrine')->getManager()->getClassMetadata(Image::class),
            null
        );
    }

    protected function getPrefix()
    {
        return $this->get('search.service')->getConfiguration()['prefix'];
    }

    protected function get($id)
    {
        return self::$kernel->getContainer()->get($id);
    }

    protected function refreshDb($application)
    {
        $inputs = [
            new ArrayInput([
                'command'         => 'doctrine:schema:drop',
                '--full-database' => true,
                '--force'         => true,
                '--quiet'         => true,
            ]),
            new ArrayInput([
                'command' => 'doctrine:schema:create',
                '--quiet' => true,
            ]),
        ];

        $application->setAutoExit(false);
        foreach ($inputs as $input) {
            $application->run($input, new ConsoleOutput());
        }
    }

    protected function getFileName($indexName, $type)
    {
        return sprintf('%s/%s-%s.json', $this->get('search.service')->getConfiguration()['settingsDirectory'], $indexName, $type);
    }

    protected function getDefaultConfig()
    {
        return [
            'hitsPerPage'       => 20,
            'maxValuesPerFacet' => 100,
        ];
    }
}
