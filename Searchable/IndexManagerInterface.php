<?php

namespace Algolia\SearchBundle\Searchable;


use Doctrine\Common\Persistence\ObjectManager;

interface IndexManagerInterface
{
    public function isSearchable($className);

    public function getIndexConfiguration();

    public function getSearchableEntities();

    public function index($entity, ObjectManager $objectManager);
}
