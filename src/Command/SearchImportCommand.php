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
            ->addArgument('indexNames', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Name of the index to reindex (without prefix)')
            ->addOption('all', false, InputOption::VALUE_NONE, 'Reindex all indices');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManager = $this->getContainer()->get('search.index_manager');
        $doctrine = $this->getContainer()->get('doctrine');
        $entitiesToIndex = $this->getEntitiesFromArgs($input, $output, $indexManager);

        foreach ($entitiesToIndex as $entityClassName) {
            $repository = $doctrine->getRepository($entityClassName);
            $manager = $doctrine->getManager();

            $entities = $repository->findAll();

            $indexManager->index($entities, $manager);

            $output->writeln('Indexed <comment>'.count($entities).'</comment> '.$entityClassName);
        }

        $output->writeln('<info>Done!</info>');
    }
}
