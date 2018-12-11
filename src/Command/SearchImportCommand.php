<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\Entity\Aggregator;
use Algolia\SearchBundle\IndexManagerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use const E_USER_DEPRECATED;
use function trigger_error;

class SearchImportCommand extends IndexCommand
{
    protected static $defaultName = 'search:import';

    /**
     * @var ManagerRegistry|null
     */
    private $managerRegistry;

    public function __construct(IndexManagerInterface $indexManager, ManagerRegistry $managerRegistry = null)
    {
        parent::__construct($indexManager);

        $this->managerRegistry = $managerRegistry;
        if ($managerRegistry === null) {
            @trigger_error('Instantiating the SearchImportCommand without a manager registry is deprecated', E_USER_DEPRECATED);
        }
    }

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entitiesToIndex = $this->getEntitiesFromArgs($input, $output);
        $config = $this->indexManager->getConfiguration();

        foreach ($entitiesToIndex as $key => $entityClassName) {
            if (is_subclass_of($entityClassName, Aggregator::class)) {
                unset($entitiesToIndex[$key]);
                $entitiesToIndex = array_merge($entitiesToIndex, $entityClassName::getEntities());
            }
        }

        $entitiesToIndex = array_unique($entitiesToIndex);

        foreach ($entitiesToIndex as $entityClassName) {
            $manager = $this->getManagerRegistry()->getManagerForClass($entityClassName);
            $repository = $manager->getRepository($entityClassName);

            $page = 0;
            do {
                $entities = $repository->findBy(
                    [],
                    null,
                    $config['batchSize'],
                    $config['batchSize'] * $page
                );
                $responses = $this->indexManager->index($entities, $manager);
                foreach ($responses as $indexName => $numberOfRecords) {
                    $output->writeln(sprintf(
                        'Indexed <comment>%s / %s</comment> %s entities into %s index',
                        $numberOfRecords,
                        count($entities),
                        $entityClassName,
                        '<info>' . $config['prefix'] . $indexName . '</info>'
                    ));
                }

                $page++;
                $repository->clear();
            } while (count($entities) >= $config['batchSize']);

            $repository->clear();
        }


        $output->writeln('<info>Done!</info>');
    }

    /**
     * @return ManagerRegistry
     */
    private function getManagerRegistry()
    {
        if ($this->managerRegistry === null) {
            $this->managerRegistry = $this->container->get('doctrine');
        }

        return $this->managerRegistry;
    }
}
