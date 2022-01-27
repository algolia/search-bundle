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
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $name;

    private $count;

    private $public;

    private $publishedAt;

    public function __construct(array $attributes = [])
    {
        $this->id          = $attributes['id'] ?? null;
        $this->name        = $attributes['name'] ?? 'This is a tag';
        $this->count       = $attributes['count'] ?? 0;
        $this->public      = $attributes['public'] ?? true;
        $this->publishedAt = $attributes['publishedAt'] ?? new \DateTime();
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

    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = []): array|string|int|float|bool
    {
        if (Searchable::NORMALIZATION_FORMAT === $format) {
            return [
                'id'          => $this->id,
                'name'        => 'this test is correct',
                'count'       => 10,
                'publishedAt' => $normalizer->normalize($this->publishedAt),
            ];
        }
    }
}
