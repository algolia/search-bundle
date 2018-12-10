<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\TestApp\Entity\Comment;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Algolia\SearchBundle\TestApp\Entity\Tag;
use Algolia\SearchBundle\Normalizer\CommentNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializationTest extends BaseTest
{
    public function testSerializerHasRequiredNormalizers()
    {
        $serializer = $this->get('serializer');

        $refl = new \ReflectionClass($serializer);
        $normalizersProperty = $refl->getProperty('normalizers');
        $normalizersProperty->setAccessible(true);
        $normalizers = $normalizersProperty->getValue($serializer);

        $classes = array_map(function ($value) {
            return get_class($value);
        }, $normalizers);

        $this->assertContains('ObjectNormalizer', end($classes));
        $this->assertContains('CustomNormalizer', $classes[1]);
        $this->assertEquals(CommentNormalizer::class, $classes[0]);
        $this->assertGreaterThan(3, count($classes));
    }

    public function testSimpleEntityToSearchableArray()
    {
        $datetime = new \DateTime();
        $dateSerializer = new Serializer([new DateTimeNormalizer()]);
        // This way we can test that DateTime's are serialized with DateTimeNormalizer
        // And not the default ObjectNormalizer
        $serializedDateTime = $dateSerializer->normalize($datetime, Searchable::NORMALIZATION_FORMAT);

        $post = new Post([
            'id' => 12,
            'title' => 'a simple post',
            'content' => 'some text',
            'publishedAt' => $datetime,
        ]);
        $post->addComment(new Comment([
            'content' => 'a great comment',
            'publishedAt' => $datetime,
            'post' => $post,
        ]));
        $postMeta = $this->get('doctrine')->getManager()->getClassMetadata(Post::class);

        $searchablePost = new SearchableEntity(
            'posts',
            $post,
            $postMeta,
            $this->get('serializer')
        );

        $expected = [
            "id" => 12,
            "title" => "a simple post",
            "content" => "some text",
            "publishedAt" => $serializedDateTime,
            "comments" => [
                [
                    "content" => "a great comment",
                    "post_title" => "a simple post",
                ]
            ],
        ];

        $this->assertEquals($expected, $searchablePost->getSearchableArray());
    }

    public function testEntityWithAnnotationsToSearchableArray()
    {
        $datetime = new \DateTime();
        $dateSerializer = new Serializer([new DateTimeNormalizer()]);
        // This way we can test that DateTime's are serialized with DateTimeNormalizer
        // And not the default ObjectNormalizer
        $serializedDateTime = $dateSerializer->normalize($datetime, Searchable::NORMALIZATION_FORMAT);

        $post = new Post([
            'id' => 12,
            'title' => 'a simple post',
            'content' => 'some text',
            'publishedAt' => $datetime,
            'comments' => [new Comment([
                'content' => 'a great comment',
                'publishedAt' => $datetime,
            ])],
        ]);
        $postMeta = $this->get('doctrine')->getManager()->getClassMetadata(Post::class);

        $searchablePost = new SearchableEntity(
            'posts',
            $post,
            $postMeta,
            $this->get('serializer'),
            ['useSerializerGroup' => true]
        );

        $expected = [
            "id" => 12,
            "title" => "a simple post",
            "publishedAt" => $serializedDateTime,
        ];

        $this->assertEquals($expected, $searchablePost->getSearchableArray());
    }

    public function testNormalizableEntityToSearchableArray()
    {
        $datetime = new \DateTime();
        $dateSerializer = new Serializer([new DateTimeNormalizer()]);
        // This way we can test that DateTime's are serialized with DateTimeNormalizer
        // And not the default ObjectNormalizer
        $serializedDateTime = $dateSerializer->normalize($datetime, Searchable::NORMALIZATION_FORMAT);

        $tag = new Tag([
            'id' => 123,
            'publishedAt' => $datetime,
        ]);
        $tagMeta = $this->get('doctrine')->getManager()->getClassMetadata(Tag::class);

        $searchableTag = new SearchableEntity(
            'tags',
            $tag,
            $tagMeta,
            $this->get('serializer'),
            ['useSerializerGroup' => true] // This should have no influence
        );

        $expected = [
            'id' => 123,
            'name' => 'this test is correct',
            'count' => 10,
            "publishedAt" => $serializedDateTime,
        ];

        $this->assertEquals($expected, $searchableTag->getSearchableArray());
    }

    public function testDedicatedNormalizer()
    {
        $comment = new Comment([
            'id' => 99,
            'content' => 'hey, this is a comment',
            'post' => new Post(['title' => 'Another super post'])
        ]);

        $searchableComment = new SearchableEntity(
            'comments',
            $comment,
            $this->get('doctrine')->getManager()->getClassMetadata(Comment::class),
            $this->get('serializer')
        );
        $expected = [
            "content" => "hey, this is a comment",
            "post_title" => "Another super post",
        ];

        $this->assertEquals($expected, $searchableComment->getSearchableArray());
    }

    public function testSimpleEntityWithJMSSerializer()
    {
        $datetime = new \DateTime();
        // The format is defined in the framework configuration (see tests/config/config.yml)
        $serializedDateTime = $datetime->format("Y-m-d\\TH:i:sP");

        $post = new Post([
            'id' => 12,
            'title' => 'a simple post',
            'content' => 'some text',
            'publishedAt' => $datetime,
            'comments' => [new Comment([
                'content' => 'a great comment',
                'publishedAt' => $datetime,
            ])],
        ]);
        $postMeta = $this->get('doctrine')->getManager()->getClassMetadata(Post::class);

        $searchablePost = new SearchableEntity(
            'posts',
            $post,
            $postMeta,
            $this->get('jms_serializer')
        );

        $expected = [
            "id" => 12,
            "title" => "a simple post",
            "content" => "some text",
            "publishedAt" => $serializedDateTime,
            "comments" => [
                [
                    "id" => null,
                    "content" => "a great comment",
                    "publishedAt" => $serializedDateTime,
                    "post" => null,
                ]
            ],
        ];

        $this->assertEquals($expected, $searchablePost->getSearchableArray());
    }
}
