<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocument
{
    /**
     * @var integer
     *
     * @ODM\Field(type="int")
     *
     * @Algolia\Attribute
     */
    protected $rating;

    /**
     * Set rating
     *
     * @param  integer $rating
     * @return EmbeddedDocument
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
