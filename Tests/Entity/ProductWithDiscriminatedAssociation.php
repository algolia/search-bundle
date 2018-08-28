<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 * @ODM\Document
 *
 * @Algolia\Index
 */
class ProductWithDiscriminatedAssociation extends BaseTestAwareEntity
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
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity=AbstractAssociation::class, mappedBy="owner", cascade={"persist", "remove"})
     * @ODM\ReferenceMany(targetDocument=AbstractAssociation::class, cascade={"persist", "remove"})
     *
     * @Algolia\Attribute
     */
    protected $associations;

    public function __construct()
    {
        $this->id = 1;
        $this->associations = new ArrayCollection([
            new AssociationA('a'),
            new AssociationB('b'),
        ]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAssociations()
    {
        return $this->associations;
    }
}

/**
 * @ORM\MappedSuperclass
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "a"=AssociationA::class,
 *     "b"=AssociationB::class,
 * })
 * @ODM\MappedSuperclass
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({
 *     "a"=AssociationA::class,
 *     "b"=AssociationB::class,
 * })
 *
 * @Algolia\Index(autoIndex=false)
 */
class AbstractAssociation
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
     * @var ProductWithDiscriminatedAssociation
     *
     * @ORM\ManyToOne(targetEntity=ProductWithDiscriminatedAssociation::class, inversedBy="associations")
     * @ORM\JoinColumn(name="owner_id", nullable=true)
     * @ODM\ReferenceOne(targetDocument=ProductWithDiscriminatedAssociation::class, mappedBy="associations")
     */
    protected $owner;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}

/**
 * @ORM\Entity
 * @ODM\Document
 */
class AssociationA extends AbstractAssociation
{
}

/**
 * @ORM\Entity
 * @ODM\Document
 */
class AssociationB extends AbstractAssociation
{
}
