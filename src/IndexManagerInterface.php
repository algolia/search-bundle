<?php

namespace Algolia\SearchBundle;


use Doctrine\Common\Persistence\ObjectManager;

interface IndexManagerInterface
{
    public function isSearchable($className);

    public function getSearchableEntities();

    public function getConfiguration();

    public function getFullIndexName($className);

    public function index($entity, ObjectManager $objectManager);

    public function remove($entity, ObjectManager $objectManager);

    public function clear($className);

    public function delete($className);

    public function search($query, $className, ObjectManager $objectManager, $page = 0, $nbResults = null, array $parameters = []);

    public function rawSearch($query, $className, $page = 0, $nbResults = null, array $parameters = []);

    public function count($query, $className, array $parameters = []);
}
