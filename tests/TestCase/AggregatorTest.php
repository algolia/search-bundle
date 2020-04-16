<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Exception\EntityNotFoundInObjectID;
use Algolia\SearchBundle\Exception\InvalidEntityForAggregator;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\EmptyAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class AggregatorTest extends BaseTest
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function setUp()
    {
        parent::setUp();

        $application = new Application(self::$kernel);
        $this->refreshDb($application);

        $this->entityManager  = $this->get('doctrine')->getManager();
    }

    public function testGetEntities()
    {
        $entites = EmptyAggregator::getEntities();

        $this->assertEquals([], $entites);
    }

    public function testGetEntityClassFromObjectID()
    {
        $this->expectException(EntityNotFoundInObjectID::class);
        EmptyAggregator::getEntityClassFromObjectID('test');
    }

    public function testContructor()
    {
        $this->expectException(InvalidEntityForAggregator::class);
        $post                = new Post();
        $compositeAggregator = new ContentAggregator($post, ['objectId', 'url']);
    }

    public function testAggregatorProxyClass()
    {
        $post = new Post([
            'id'      => 1,
            'title'   => 'Test',
            'content' => 'Test content',
        ]);
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $postMetadata = $this->entityManager->getClassMetadata(Post::class);
        $this->entityManager->getProxyFactory()->generateProxyClasses([$postMetadata], null);

        $proxy             = $this->entityManager->getProxyFactory()->getProxy($postMetadata->getName(), ['id' => 1]);
        $contentAggregator = new ContentAggregator($proxy, ['objectId']);

        $serializer = $this->get('serializer');

        $serializedData = $contentAggregator->normalize($serializer);
        $this->assertNotEmpty($serializedData);
        $this->assertEquals('Algolia\SearchBundle\TestApp\Entity\Post::objectId', $serializedData['objectID']);
    }
}
