<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Algolia\SearchBundle\Entity\Aggregator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ContentAggregator extends Aggregator
{
    /**
     * @var bool
     */
    private $isVisible;

    public function __construct($entity, array $entityIdentifierValues)
    {
        parent::__construct($entity, $entityIdentifierValues);

        $this->isVisible = true;

        if ($entity instanceof Post && $entity->getTitle() === 'Foo') {
            $this->isVisible = false;
        }
    }

    public function getIsVisible()
    {
        return $this->isVisible;
    }

    public static function getEntities()
    {
        return [
            Post::class,
            Comment::class,
            Image::class
        ];
    }
}
