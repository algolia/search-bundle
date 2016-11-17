<?php

namespace Algolia\AlgoliaSearchBundle\Mapping;

class Description
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var Index
     */
    private $index;

    /**
     * @var Property[]
     */
    private $properties = [];

    /**
     * @var Method[]
     */
    private $methods = [];

    /**
     * @var IndexIf[]
     */
    private $indexIfs = [];

    /**
     * @var string[]
     */
    private $identifierAttributeNames = [];

    /**
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @param Index $index
     * @return $this
     */
    public function setIndex(Index $index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return Index
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param Method $m
     * @return $this
     */
    public function addMethod(Method $m)
    {
        $this->methods[] = $m;

        return $this;
    }

    /**
     * @return Method[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param Property $p
     * @return $this
     */
    public function addProperty(Property $p)
    {
        $this->properties[] = $p;

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->properties) && empty($this->methods);
    }

    /**
     * @param string[] $fields
     * @return $this
     */
    public function setIdentifierAttributeNames(array $fields)
    {
        $this->identifierAttributeNames = $fields;

        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function addIdentifierAttributeName($field)
    {
        $this->identifierAttributeNames[] = $field;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getIdentifierFieldNames()
    {
        return $this->identifierAttributeNames;
    }

    /**
     * @return bool
     */
    public function hasIdentifierFieldNames()
    {
        return !empty($this->identifierAttributeNames);
    }

    /**
     * @param IndexIf $iif
     * @return $this
     */
    public function addIndexIf(IndexIf $iif)
    {
        $this->indexIfs[] = $iif;

        return $this;
    }

    /**
     * @return IndexIf[]
     */
    public function getIndexIfs()
    {
        return $this->indexIfs;
    }
}
