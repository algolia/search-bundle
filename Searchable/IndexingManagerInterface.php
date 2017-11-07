<?php

namespace Algolia\SearchBundle\Searchable;


use Doctrine\Common\Persistence\ObjectManager;

interface IndexingManagerInterface
{
    public function isSearchable($className);

    public function index($entity, ObjectManager $objectManager);

    public function delete($entity, ObjectManager $objectManager);

    public function clear($indexName);
}
