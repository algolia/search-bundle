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

    // TODO: Add $parameters argument
    // The 3 arguments named $parameters will be added to this interface
    // when we release the next major version
    // See https://github.com/algolia/search-bundle/issues/259
    public function count($query, $indexName /*, array $parameters = [] */);
}
