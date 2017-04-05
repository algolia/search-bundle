<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ODM\Document
 *
 * @Algolia\Index
 */
class ProductWithEmbeddedDocument extends BaseTestAwareEntity
{
    /**
     * @var integer
     *
     * @ODM\Id()
     */
    protected $id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $name;

    /**
     * @var string
     *
     * @ODM\Field(type="float")
     *
     * @Algolia\Attribute
     */
    protected $price;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $shortDescription;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     *
     * @Algolia\Attribute
     */
    protected $description;

    /**
     * @var EmbeddedDocument
     *
     * @ODM\EmbedOne(targetDocument=EmbeddedDocument::class)
     *
     * @Algolia\Attribute
     */
    protected $embed;

    public function __construct()
    {
        $this->embed = new EmbeddedDocument();
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
     * @return ProductWithEmbeddedDocument
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
     * @return ProductWithEmbeddedDocument
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
     * @return ProductWithEmbeddedDocument
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
     * @return ProductWithEmbeddedDocument
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
     * @return ProductWithEmbeddedDocument
     */
    public function setRating($rating)
    {
        $this->embed->setRating($rating);

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->embed->getRating();
    }

    /**
     * @return EmbeddedDocument
     */
    public function getEmbed()
    {
        return $this->embed;
    }
}
