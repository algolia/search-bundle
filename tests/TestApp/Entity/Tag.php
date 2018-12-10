<?php

namespace Algolia\SearchBundle\TestApp\Entity;

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

    private $name;

    private $public;

    private $publishedAt;

    public function __construct(array $attributes = [])
    {
        $this->id = isset($attributes['id']) ? $attributes['id'] : null;
        $this->name = isset($attributes['name']) ? $attributes['name'] : 'This is a tag';
        $this->count = isset($attributes['count']) ? $attributes['count'] : 0;
        $this->public = isset($attributes['public']) ? $attributes['public'] : true;
        $this->publishedAt = isset($attributes['publishedAt']) ? $attributes['publishedAt'] : new \DateTime();
    }

    public function isPublic()
    {
        return $this->public;
    }

    public function setPublic($public)
    {
        $this->public = $public;
        return $this;
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
