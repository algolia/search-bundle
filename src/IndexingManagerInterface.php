<?php

namespace Algolia\SearchBundle;

use Doctrine\Common\Persistence\ObjectManager;

interface IndexingManagerInterface
{
    public function isSearchable($className);

    public function getSearchableEntities();

    public function getConfiguration();

    public function getFullIndexName($className);

    public function index($entity, ObjectManager $objectManager);

    public function remove($entity, ObjectManager $objectManager);

    public function clear($className);

    public function delete($className);
}
