<?php

namespace Algolia\SearchBundle\TestApp\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 */
class Image
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"searchable"})
     */
    private $id;

    /**
     * @var string
     */
    private $url;

    public function __construct(array $attributes = [])
    {
        $this->id = isset($attributes['id']) ? $attributes['id'] : null;
        $this->url = isset($attributes['url']) ? $attributes['url'] : '/wp-content/uploads/flamingo.jpg';
    }

    /**
     * @Groups({"searchable"})
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @Groups({"searchableCustom"})
     */
    public function getCustomVirtualProperty()
    {
        return 'here';
    }
}
