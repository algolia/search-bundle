<?php

namespace Algolia\AlgoliaSearchBundle\Indexer\Query;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ORM
{
    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $entityName;

    /**
     * @param EntityManager $entityManager
     * @param string $entityName
     */
    public function __construct(EntityManager $entityManager, $entityName)
    {
        $this->entityManager = $entityManager;
        $this->entityName = $entityName;
    }

    /**
     * @param string $batchSize
     * @param callable $callback
     * @param Query|null $query
     * @param bool $clearObjectManager
     * @return int
     */
    public function batchQuery($batchSize, $callback, Query $query = null, $clearObjectManager = false)
    {
        if (! $query) {
            $query = $this->entityManager->createQueryBuilder()->select('e')->from($this->entityName, 'e')->getQuery();
        }

        $entitiesIndexed = 0;

        for ($page = 0;; $page += 1) {
            $query
                ->setFirstResult($batchSize * $page)
                ->setMaxResults($batchSize);

            $paginator = new Paginator($query);

            $batch = [];
            foreach ($paginator as $entity) {
                $batch[] = $entity;
            }

            if (empty($batch)) {
                break;
            } else {
                $entitiesIndexed += count($batch);
                $callback($batch);
            }

            if ($clearObjectManager) {
                $this->entityManager->clear();
            }
        }

        return $entitiesIndexed;
    }
}
