<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * Product
 *
 * @ORM\Entity
 * @Algolia\Index(algoliaName="getDynamicIndexName")
 */
class ProductWithDynamicIndexName extends BaseTestAwareEntity
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
     * @Algolia\Attribute
     */
    protected $name;

    public function getDynamicIndexName()
    {
        return $this->getName().'_dynamic_index';
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
}
