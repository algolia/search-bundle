<?php

namespace Algolia\SearchBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchImportCommand extends IndexCommand
{
    protected function configure()
    {
        $this
            ->setName('search:import')
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
        $doctrine = $this->getContainer()->get('doctrine');
        $entitiesToIndex = $this->getEntitiesFromArgs($input, $output);
        $config = $this->indexManager->getConfiguration();

        foreach ($entitiesToIndex as $indexName => $entityClassName) {
            $manager = $doctrine->getManagerForClass($entityClassName);
            $repository = $manager->getRepository($entityClassName);

            $page = 0;
            do {
                $entities = $repository->findBy(
                    [],
                    null,
                    $config['batchSize'],
                    $config['batchSize'] * $page
                );
                $response = $this->indexManager->index($entities, $manager);

                $output->writeln(sprintf(
                    'Indexed <comment>%s / %s</comment> %s entities into %s index',
                    isset($response[$indexName]) ? $response[$indexName] : 0,
                    count($entities),
                    $entityClassName,
                    '<info>' . $config['prefix'] . $indexName . '</info>'
                ));

                $page++;
                $repository->clear();
            } while (count($entities) >= $config['batchSize']);

            $repository->clear();
        }


        $output->writeln('<info>Done!</info>');
    }
}
