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
 *     searchableAttributes = {"name", "price", "shortDescription", "description", "rating"},
 *     numericAttributesForFiltering = {"rating", "price"},
 *     highlightPreTag = "<strong>",
 *     highlightPostTag = "</strong>",
 *     replicas = {"test"}
 * )
 */
class ProductForAlgoliaIntegrationTest extends BaseTestAwareEntity
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", nullable=true)
     * @ODM\Field(type="float")
     *
     * @Algolia\Attribute
     */
    protected $price;

    /**
     * @var string
     *
     * @ORM\Column(name="short_description", type="string", length=255, nullable=true)
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $shortDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="integer", nullable=true)
     * @ODM\Field(type="int")
     *
     * @Algolia\Attribute
     */
    protected $rating;

    /**
     * @Algolia\IndexIf
     *
     * Rationale: we don't want people to search for products for which we haven't
     * set both a price and at least a short description.
     */
    public function isReadyForSale()
    {
        return $this->getPrice() > 0 && $this->getShortDescription() !== '';
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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
     * @Algolia\Attribute
     */
    public function getYoName()
    {
        return 'YO ' . $this->getName();
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
