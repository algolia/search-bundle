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
    public function setUp(): void
    {
        self::bootKernel();
    }

    protected function createPost($id = null): Post
    {
        $post = new Post();
        $post->setTitle('Test');
        $post->setContent('Test content');
        if (!is_null($id)) {
            $post->setId($id);
        }

        return $post;
    }

    protected function createSearchablePost(): SearchableEntity
    {
        $post = $this->createPost(random_int(100, 300));

        return new SearchableEntity(
            $this->getPrefix() . 'posts',
            $post,
            $this->get('doctrine')->getManager()->getClassMetadata(Post::class),
            $this->get('serializer')
        );
    }

    protected function createComment($id = null): Comment
    {
        $comment = new Comment();
        $comment->setContent('Comment content');
        $comment->setPost(new Post(['title' => 'What a post!', 'content' => 'my content']));

        if (!is_null($id)) {
            $comment->setId($id);
        }

        return $comment;
    }

    protected function createImage($id = null): Image
    {
        $image = new Image();

        if (!is_null($id)) {
            $image->setId($id);
        }

        return $image;
    }

    protected function createSearchableImage(): SearchableEntity
    {
        $image = $this->createImage(random_int(100, 300));

        return new SearchableEntity(
            $this->getPrefix() . 'image',
            $image,
            $this->get('doctrine')->getManager()->getClassMetadata(Image::class),
            null
        );
    }

    protected function getPrefix(): ?string
    {
        return $this->get('search.service')->getConfiguration()['prefix'];
    }

    protected function get($id): ?object
    {
        return self::$kernel->getContainer()->get($id);
    }

    protected function refreshDb($application): void
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

    protected function getFileName($indexName, $type): string
    {
        return sprintf(
            '%s/%s-%s.json',
            $this->get('search.service')->getConfiguration()['settingsDirectory'],
            $indexName,
            $type
        );
    }

    protected function getDefaultConfig(): array
    {
        return [
            'hitsPerPage'       => 20,
            'maxValuesPerFacet' => 100,
        ];
    }
}
