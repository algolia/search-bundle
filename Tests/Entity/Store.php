<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 * @ODM\Document
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
     *
     * @ODM\Id(strategy="increment")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255)
     * @ODM\Field(type="string")
     */
    private $address;

    /**
     * @var  double
     *
     * @ORM\Column(name="lat", type="float")
     * @ODM\Field(type="float")
     */
    private $lat;

    /**
     * @var  double
     *
     * @ORM\Column(name="lng", type="float")
     * @ODM\Field(type="float")
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
