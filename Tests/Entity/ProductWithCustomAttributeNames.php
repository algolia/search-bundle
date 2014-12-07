<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation as Algolia;

/**
 * Product
 *
 * @ORM\Entity
 * @Algolia\Index(algoliaName="nonDefaultIndexName")
 */
class ProductWithCustomAttributeNames extends ORMEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * 
     * @Algolia\Attribute(algoliaName="nonDefaultAttributeName")
     */
    protected $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
