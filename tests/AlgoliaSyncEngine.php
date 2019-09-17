<?php

namespace Algolia\SearchBundle;

class AlgoliaSyncEngine extends AlgoliaEngine
{
    public function add($searchableEntities, $requestOptions = [])
    {
        return $this->update($searchableEntities, $requestOptions);
    }

    public function update($searchableEntities, $requestOptions = [])
    {
        $batch = $this->doUpdate($searchableEntities, $requestOptions);

        foreach ($batch as $indexName => $response) {
            $response->wait();
        }

        return $this->formatIndexingResponse($batch);
    }

    public function remove($searchableEntities, $requestOptions = [])
    {
        $batch = $this->doRemove($searchableEntities, $requestOptions);

        foreach ($batch as $indexName => $response) {
            $response->wait();
        }

        return $this->formatIndexingResponse($batch);
    }

    public function clear($indexName)
    {
        try {
            $batch = $this->doClear($indexName);

            foreach ($batch as $name => $response) {
                $response->wait();
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

            foreach ($batch as $name => $response) {
                $response->wait();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
