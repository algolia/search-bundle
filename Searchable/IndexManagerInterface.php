<?php

namespace Algolia\SearchBundle\Searchable;


use Doctrine\Common\Persistence\ObjectManager;

interface IndexManagerInterface
{
    public function index($entity, ObjectManager $objectManager);
    public function isSearchable($className);
}
