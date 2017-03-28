<?php

namespace Algolia\AlgoliaSearchBundle\Indexer\Query;

use Doctrine\ODM\MongoDB\Cursor;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Query;

class ODM
{
    /** @var DocumentManager */
    private $documentManager;

    /** @var string */
    private $documentName;

    /**
     * @param DocumentManager $documentManager
     * @param string $documentName
     */
    public function __construct(DocumentManager $documentManager, $documentName)
    {
        $this->documentManager = $documentManager;
        $this->documentName = $documentName;
    }

    /**
     * @param int $batchSize
     * @param callable $callback
     * @param Query|null $query
     * @param bool $clearObjectManager
     * @return int
     */
    public function batchQuery($batchSize, $callback, Query $query = null, $clearObjectManager = false)
    {
        if (! $query) {
            $query = $this->documentManager->createQueryBuilder($this->documentName)->getQuery();
        }

        $documentsIndexed = 0;

        /** @var Cursor $cursor */
        $cursor = $query->execute();
        for ($page = 0;; $page += 1) {
            $cursor
                ->skip($batchSize * $page)
                ->limit($batchSize);

            $batch = $cursor->toArray(false);

            if (empty($batch)) {
                break;
            } else {
                $documentsIndexed += count($batch);
                $callback($batch);
            }

            if ($clearObjectManager) {
                $this->documentManager->clear();
            }

            $cursor->recreate();
        }

        return $documentsIndexed;
    }
}
