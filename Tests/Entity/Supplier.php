<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection as Collection;

use Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping\Annotation as Algolia;

/**
 * Store
 *
 * @ORM\Entity
 *
 */
class Supplier extends BaseTestAwareEntity
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
     * @ORM\OneToMany(targetEntity="Product", mappedBy="supplier", cascade={"persist", "remove"})
     */
    private $products;

    public function __construct()
    {
        $this->products = new Collection();
    }

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

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct($product)
    {
        $this->products->add($product);

        return $this;
    }
}
