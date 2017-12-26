<?php

namespace Algolia\SearchBundle\Engine;


class AlgoliaSyncEngine extends AlgoliaEngine
{
    public function add($searchableEntities)
    {
        $res = parent::add($searchableEntities);

        foreach ($res as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $res;
    }

    public function update($searchableEntities)
    {
        $res = parent::update($searchableEntities);

        foreach ($res as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $res;
    }

    public function remove($searchableEntities)
    {
        $res = parent::remove($searchableEntities);

        foreach ($res as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $res;
    }

    public function clear($indexName)
    {
        $res = parent::clear($indexName);

        foreach ($res as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $res;
    }
}
