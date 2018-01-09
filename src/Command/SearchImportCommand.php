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

        foreach ($entitiesToIndex as $indexName => $entityClassName) {
            $repository = $doctrine->getRepository($entityClassName);
            $manager = $doctrine->getManager();

            $entities = $repository->findAll();

            $this->indexManager->index($entities, $manager);

            $output->writeln(sprintf(
                'Indexed %s %s entities into %s index',
                '<comment>'.count($entities).'</comment>',
                $entityClassName,
                '<info>'.$indexName.'</info>'
            ));
        }

        $output->writeln('<info>Done!</info>');
    }
}
