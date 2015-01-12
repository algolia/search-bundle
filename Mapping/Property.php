<?php

namespace Algolia\AlgoliaSearchBundle\Mapping;

class Property
{
    private $name;
    private $algoliaName;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAlgoliaName($algoliaName)
    {
        $this->algoliaName = $algoliaName;

        return $this;
    }

    public function getAlgoliaName()
    {
        return $this->algoliaName;
    }
}
