<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 * @ODM\Document
 */
class ProductWithMultipleCustomObjectIds extends BaseTestAwareEntity
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Id
     * @Algolia\Id
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $name;

    /**
     * @var float
     * @ORM\Column(name="price", type="decimal", nullable=true)
     * @ODM\Field(type="float")
     */
    protected $price;

    /**
     * @var string
     * @Algolia\Id
     *
     * @ORM\Column(name="short_description", type="string", length=255, nullable=true)
     * @ODM\Field(type="string")
     */
    protected $shortDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ODM\Field(type="string")
     */
    protected $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="integer", nullable=true)
     * @ODM\Field(type="int")
     */
    protected $rating;

    /**
     * @ORM\ManyToOne(targetEntity="Supplier", inversedBy="products")
     * @ORM\JoinColumn(name="supplier_id", nullable=true)
     * @ODM\ReferenceOne(targetDocument="Supplier")
     */
    protected $supplier;

    /**
     * Set name
     *
     * @param  string  $name
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set price
     *
     * @param  string  $price
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set shortDescription
     *
     * @param  string  $shortDescription
     * @return Product
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Set description
     *
     * @param  string  $description
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set rating
     *
     * @param  integer $rating
     * @return Product
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->rating;
    }
}
