<?php

namespace Algolia\SearchBundle\Searchable;


use Doctrine\Common\Persistence\ObjectManager;

interface SearchManagerInterface
{
    public function isSearchable($className);

    public function search($query, $className, ObjectManager $objectManager, $nbResults = 20, $page = 0, array $parameters = []);

    public function rawSearch($query, $className, $nbResults = 20, $page = 0, array $parameters = []);

    public function count($query, $className, array $parameters = []);
}
