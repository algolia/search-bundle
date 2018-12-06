<?php

namespace Algolia\SearchBundle\Engine;


class NullEngine implements EngineInterface
{

    public function add($searchableEntities)
    {
        //
    }

    public function update($searchableEntities)
    {
        //
    }

    public function remove($searchableEntities)
    {
        //
    }

    public function clear($indexName)
    {
        //
    }

    public function delete($indexName)
    {
        //
    }

    public function search($query, $indexName, $page = 0, $nbResults = null, array $parameters = [])
    {
        return [];
    }

    public function searchIds($query, $indexName, $page = 0, $nbResults = null, array $parameters = [])
    {
        return [];
    }

    public function count($query, $indexName /*, array $parameters = [] */)
    {
        return 0;
    }
}
