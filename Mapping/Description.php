<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Mapping;

class Description
{
    private $class;
    private $index;
    private $properties = [];
    private $methods = [];
    private $indexIfs = [];

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function setIndex(Index $index)
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function addMethod(Method $m)
    {
        $this->methods[] = $m;

        return $this;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function addProperty(Property $p)
    {
        $this->properties[] = $p;

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function isEmpty()
    {
        return empty($this->properties) && empty($this->methods);
    }

    public function setIdentifierFieldNames(array $fields)
    {
        $this->identifierFieldNames = $fields;

        return $this;
    }

    public function getIdentifierFieldNames()
    {
        return $this->identifierFieldNames;
    }

    public function addIndexIf(IndexIf $iif)
    {
        $this->indexIfs[] = $iif;

        return $this;
    }

    public function getIndexIfs()
    {
        return $this->indexIfs;
    }
}
