<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Algolia\SearchBundle\Searchable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="links")
 */
class Link implements NormalizableInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    private $name;

    private $url;

    public function __construct(array $attributes = [])
    {
        $this->id   = $attributes['id'] ?? null;
        $this->name = $attributes['name'] ?? 'This is a tag';
        $this->url  = $attributes['url'] ?? null;
    }

    private function isSponsored()
    {
        return false;
    }

    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = []): array|string|int|float|bool
    {
        if (Searchable::NORMALIZATION_FORMAT === $format) {
            return [
                'id'   => $this->id,
                'name' => 'this test is correct',
                'url'  => 'https://algolia.com',
            ];
        }
    }
}
