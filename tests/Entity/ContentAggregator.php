<?php

namespace Algolia\SearchBundle\Entity;


use Algolia\SearchBundle\Aggregator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class ContentAggregator extends Aggregator
{
    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
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
        ];
    }
}
