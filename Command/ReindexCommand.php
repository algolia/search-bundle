<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ReindexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('algolia:reindex')
        ->setDescription('Reindex all entities or just those of a specified type.')
        ->addArgument('entityName', InputArgument::OPTIONAL, 'Which type of entity do you want to reindex? If not set, all is assumed.')
        ->addOption('unsafe', null, InputOption::VALUE_NONE, 'Index inplace, without deleting out-dated records.')
        ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Specify a batch size for the reindexing operation.', 1000)
        ->addOption('sync', null, InputOption::VALUE_NONE, 'Wait for operations to complete before returning.')
        ;
    }

    protected function getEntityManager()
    {
        return $this
        ->getContainer()
        ->get('doctrine.orm.entity_manager');
    }

    protected function getEntityClasses()
    {
        $metaData = $this->getEntityManager()
        ->getMetadataFactory()
        ->getAllMetaData();

        return array_map(function ($data) {
            return $data->getName();
        }, $metaData);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $toReindex = [];

        if ($input->hasArgument('entityName')) {
            $filter = $this->getEntityManager()->getRepository($input->getArgument('entityName'))->getClassName();
        } else {
            $filter = null;
        }

        foreach ($this->getEntityClasses() as $class) {
            if (!$filter || $class === $filter) {
                $toReindex[] = $class;
            }
        }

        $batchSize = (int)$input->getOption('batch-size');
        if ($batchSize === 0) {
            $output->writeln('<comment>Invalid batch size specified, assuming 1000.</comment>');
            $batchSize = 1000;
        }

        $safe = !$input->getOption('unsafe');

        $nIndexed = 0;
        foreach ($toReindex as $className) {
            $nIndexed += $this->reIndex($className, $batchSize, $safe);
        }

        if ($input->getOption('sync')) {
            $this->getContainer()->get('algolia.indexer')->waitForAlgoliaTasks();
        }

        return $nIndexed;
    }

    public function reIndex($className, $batchSize = 1000, $safe = true)
    {
        $indexer = $this->getContainer()->get('algolia.indexer');
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder()->select('e')->from($className, 'e')->getQuery();

        $finalIndexName = $indexName = $masterIndexName = $indexer->getAlgoliaIndexName($className);
        if ($safe) {

            $indexName .= '__TEMPORARY__INDEX__';

            try {
                // Copy settings from master index to temporary index
                $masterSettings = $indexer->getIndex($masterIndexName)->getSettings();
                $indexer->getIndex($indexName)->setSettings($masterSettings);
            } catch (\AlgoliaSearch\AlgoliaException $e) {
                // It's OK if the master index did not exist! No settings to set.
                if ($e->getMessage() !== 'Index does not exist') {
                    throw $e;
                }
            }
        }

        $nIndexed = 0;

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
                $nIndexed += count($batch);
                $indexer->index($em, $batch, ['indexName' => $indexName]);
            }
        }

        if ($safe) {
            $indexer->algoliaTask(
                $finalIndexName,
                $indexer->getClient()->moveIndex($indexName, $finalIndexName)
            );
        }

        return $nIndexed;
    }
}