<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\Entity\Aggregator;
use Algolia\SearchBundle\SearchService;
use Doctrine\Common\Persistence\ManagerRegistry;
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
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

    /**
     * @param SearchService   $searchService
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(SearchService $searchService, ManagerRegistry $managerRegistry)
    {
        parent::__construct($searchService);

        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Import given entity into search engine')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entitiesToIndex = $this->getEntitiesFromArgs($input, $output);
        $config          = $this->searchService->getConfiguration();

        foreach ($entitiesToIndex as $key => $entityClassName) {
            if (is_subclass_of($entityClassName, Aggregator::class)) {
                unset($entitiesToIndex[$key]);
                $entitiesToIndex = array_merge($entitiesToIndex, $entityClassName::getEntities());
            }
        }

        $entitiesToIndex = array_unique($entitiesToIndex);

        foreach ($entitiesToIndex as $entityClassName) {
            $manager    = $this->managerRegistry->getManagerForClass($entityClassName);
            $repository = $manager->getRepository($entityClassName);

            $page = 0;
            do {
                $entities = $repository->findBy(
                    [],
                    null,
                    $config['batchSize'],
                    $config['batchSize'] * $page
                );

                $responses = $this->formatIndexingResponse(
                    $this->searchService->index($entities, $manager)
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

            $repository->clear();
        }

        $output->writeln('<info>Done!</info>');

        return null;
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
