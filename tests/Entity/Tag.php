<?php

namespace Algolia\SearchBundle\Entity;


use Algolia\SearchBundle\Searchable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="tags")
 */
class Tag implements NormalizableInterface
{

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     */
    private $id;

    private $count;

    private $publishedAt;

    public function __construct(array $attributes = [])
    {
        $this->id = isset($attributes['id']) ? $attributes['id'] : null;
        $this->count = isset($attributes['title']) ? $attributes['title'] : 0;
        $this->publishedAt = isset($attributes['publishedAt']) ? $attributes['publishedAt'] : new \DateTime();
    }

    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = array())
    {
        if (Searchable::NORMALIZATION_FORMAT === $format) {
            return [
                'id' => $this->id,
                'name' => 'this test is correct',
                'count' => 10,
                'publishedAt' => $normalizer->normalize($this->publishedAt)
            ];
        }
    }
}
