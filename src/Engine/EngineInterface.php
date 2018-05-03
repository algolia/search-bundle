<?php

namespace Algolia\SearchBundle\Engine;

interface EngineInterface
{
    public function add($searchableEntities);

    public function update($searchableEntities);

    public function remove($searchableEntities);

    public function clear($indexName);

    public function delete($indexName);

    public function search($query, $indexName, $page = 0, $nbResults = null, array $parameters = []);

    public function searchIds($query, $indexName, $page = 0, $nbResults = null, array $parameters = []);

    public function count($query, $indexName);
}
