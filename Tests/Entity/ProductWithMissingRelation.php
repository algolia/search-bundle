<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 * @ODM\Document
 */
class ProductWithMissingRelation extends BaseTestAwareEntity
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
     * @var Supplier
     *
     * @ORM\ManyToOne(targetEntity="Supplier", inversedBy="products")
     * @ORM\JoinColumn(name="supplier_id", nullable=true)
     * @ODM\ReferenceOne(targetDocument="Supplier")
     *
     * @Algolia\Attribute
     */
    protected $supplier;

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
     * @return Supplier
     *
     * @throws EntityNotFoundException
     */
    public function getSupplier()
    {
        throw new EntityNotFoundException('Entity of type '.self::class.' was not found');
    }

    /**
     * @param Supplier $supplier
     *
     * @return $this
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
