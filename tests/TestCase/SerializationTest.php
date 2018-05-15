<?php

namespace Algolia\SearchBundle;

use Algolia\SearchBundle\Entity\Comment;
use Algolia\SearchBundle\Entity\Post;
use Algolia\SearchBundle\Entity\Tag;
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
        $this->assertContains('CustomNormalizer', reset($classes));
        $this->assertGreaterThan(2, count($classes));
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
            $this->get('serializer')
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
}
