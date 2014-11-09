<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation as Algolia;

/**
 * Store
 *
 * @ORM\Entity
 *
 * @Algolia\Index(
 *  perEnvironment = true
 * )
 */
class Store extends BaseTestAwareEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Algolia\Attribute
     *
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal")
     *
     */
    private $address;

    /**
     * @var  double
     *
     * @ORM\Column(name="lat", type="float")
     */
    private $lat;

    /**
     * @var  double
     *
     * @ORM\Column(name="lng", type="float")
     */
    private $lng;

    public function getName()
    {
        return $this->name;
    }
    public function getAddress()
    {
        return $this->address;
    }
    public function getLat()
    {
        return $this->lat;
    }
    public function getLng()
    {
        return $this->lng;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }
}
