<?php

namespace Algolia\SearchBundle\Engine;


class AlgoliaSyncEngine extends AlgoliaEngine
{
    public function add($searchableEntities)
    {
        return $this->update($searchableEntities);
    }

    public function update($searchableEntities)
    {
        $batch = $this->doUpdate($searchableEntities);

        foreach ($batch as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $this->formatIndexingResponse($batch);
    }

    public function remove($searchableEntities)
    {
        $batch = $this->doRemove($searchableEntities);

        foreach ($batch as $indexName => $response) {
            $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
        }

        return $this->formatIndexingResponse($batch);
    }

    public function clear($indexName)
    {
        try {
            $batch = $this->doClear($indexName);

            foreach ($batch as $indexName => $response) {
                $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function delete($indexName)
    {
        try {
            $batch = $this->doDelete($indexName);

            foreach ($batch as $indexName => $response) {
                $this->algolia->initIndex($indexName)->waitTask($response['taskID']);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
