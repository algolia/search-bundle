<?php

namespace Algolia\AlgoliaSearchBundle\Tests\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;

/**
 * @ORM\Entity
 * @ODM\Document
 *
 * @Algolia\Index(algoliaName="nonDefaultIndexName")
 */
class ProductWithCustomAttributeNames extends BaseTestAwareEntity
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
