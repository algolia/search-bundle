<?php

namespace Algolia\SearchBundle\Command;

use Algolia\AlgoliaSearch\SearchClient;
use Algolia\SearchBundle\Entity\Aggregator;
use Algolia\SearchBundle\SearchService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class SearchImportCommand extends IndexCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'search:import';

    /**
     * @var SearchService
     */
    private $searchServiceForAtomicReindex;

    /**
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

    /**
     * @var SearchClient
     */
    private $searchClient;

    public function __construct(
        SearchService $searchService,
        SearchService $searchServiceForAtomicReindex,
        ManagerRegistry $managerRegistry,
        SearchClient $searchClient
    ) {
        parent::__construct($searchService);

        $this->searchServiceForAtomicReindex = $searchServiceForAtomicReindex;
        $this->managerRegistry               = $managerRegistry;
        $this->searchClient                  = $searchClient;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Import given entity into search engine')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addOption('atomic', null, InputOption::VALUE_NONE, <<<EOT
If set, command replaces all records in an index without any downtime. It pushes a new set of objects and removes all previous ones.

Internally, this option causes command to copy existing index settings, synonyms and query rules and indexes all objects. Finally, the existing index is replaced by the temporary one.
EOT
            )
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shouldDoAtomicReindex = $input->getOption('atomic');
        $entitiesToIndex       = $this->getEntitiesFromArgs($input, $output);
        $config                = $this->searchService->getConfiguration();
        $indexingService       = ($shouldDoAtomicReindex ? $this->searchServiceForAtomicReindex : $this->searchService);

        foreach ($entitiesToIndex as $key => $entityClassName) {
            if (is_subclass_of($entityClassName, Aggregator::class)) {
                unset($entitiesToIndex[$key]);
                $entitiesToIndex = array_merge($entitiesToIndex, $entityClassName::getEntities());
            }
        }

        $entitiesToIndex = array_unique($entitiesToIndex);

        foreach ($entitiesToIndex as $entityClassName) {
            if (!$this->searchService->isSearchable($entityClassName)) {
                continue;
            }

            $manager         = $this->managerRegistry->getManagerForClass($entityClassName);
            $repository      = $manager->getRepository($entityClassName);
            $sourceIndexName = $this->searchService->searchableAs($entityClassName);

            if ($shouldDoAtomicReindex) {
                $temporaryIndexName = $this->searchServiceForAtomicReindex->searchableAs($entityClassName);
                $output->writeln("Creating temporary index <info>$temporaryIndexName</info>");
                $this->searchClient->copyIndex($sourceIndexName, $temporaryIndexName, ['scope' => ['settings', 'synonyms', 'rules']]);
            }

            $page = 0;
            do {
                $entities = $repository->findBy(
                    [],
                    null,
                    $config['batchSize'],
                    $config['batchSize'] * $page
                );

                $responses = $this->formatIndexingResponse(
                    $indexingService->index($manager, $entities)
                );
                foreach ($responses as $indexName => $numberOfRecords) {
                    $output->writeln(sprintf(
                        'Indexed <comment>%s / %s</comment> %s entities into %s index',
                        $numberOfRecords,
                        count($entities),
                        $entityClassName,
                        '<info>' . $indexName . '</info>'
                    ));
                }

                $page++;
                $repository->clear();
            } while (count($entities) >= $config['batchSize']);

            if ($shouldDoAtomicReindex && isset($indexName)) {
                $output->writeln("Moving <info>$indexName</info> -> <comment>$sourceIndexName</comment>\n");
                $this->searchClient->moveIndex($indexName, $sourceIndexName);
            }

            $repository->clear();
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @param array<int, array> $batch
     *
     * @return array<string, int>
     */
    private function formatIndexingResponse($batch)
    {
        $formattedResponse = [];

        foreach ($batch as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                if (!array_key_exists($indexName, $formattedResponse)) {
                    $formattedResponse[$indexName] = 0;
                }

                $formattedResponse[$indexName] += count($apiResponse->current()['objectIDs']);
            }
        }

        return $formattedResponse;
    }
}
