<?php

namespace Algolia\SearchBundle\TestCase;

use Algolia\SearchBundle\BaseTest;
use Algolia\SearchBundle\Exception\EntityNotFoundInObjectID;
use Algolia\SearchBundle\Exception\InvalidEntityForAggregator;
use Algolia\SearchBundle\TestApp\Entity\ContentAggregator;
use Algolia\SearchBundle\TestApp\Entity\EmptyAggregator;
use Algolia\SearchBundle\TestApp\Entity\Post;

class AggregatorTest extends BaseTest
{
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
}
