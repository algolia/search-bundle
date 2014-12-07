<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation as Algolia;

/**
 * Product
 *
 * @MongoDB\Document
 *
 */
class MongoProduct extends MongoEntity
{
    /**
     * @var integer
     *
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoDB\String
     *
     * @Algolia\Attribute
     *
     */
    protected $name;

    /**
     * @var float
     *
     * @MongoDB\Float
     *
     */
    protected $price;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    protected $shortDescription;

    /**
     * @var string
     *
     * @MongoDB\String
     */
    protected $description;

    /**
     * @var integer
     *
     * @MongoDB\Int
     */
    protected $rating;

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
