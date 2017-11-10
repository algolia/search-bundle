<?php

namespace Algolia\SearchBundle\Searchable;


use Doctrine\Common\Persistence\ObjectManager;

interface IndexingManagerInterface
{
    public function isSearchable($className);

    public function getSearchableEntities();

    public function index($entity, ObjectManager $objectManager);

    public function remove($entity, ObjectManager $objectManager);

    public function clear($className);

    public function delete($className);
}
