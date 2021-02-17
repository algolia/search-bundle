<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Algolia\SearchBundle\Entity\Aggregator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ContentAggregator extends Aggregator
{
    public function getIsVisible(): bool
    {
        if ($this->entity instanceof Post) {
            return $this->entity->getTitle() !== 'Foo';
        }

        return true;
    }

    public static function getEntities(): array
    {
        return [
            Post::class,
            Comment::class,
            Image::class,
        ];
    }
}
