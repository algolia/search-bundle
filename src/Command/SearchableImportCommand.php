<?php

namespace Algolia\SearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchableImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('searchable:import')
            ->setDescription('Import given entity into search engine')
            ->addArgument('entities', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Entities to reindex')
            ->addOption('all', false, InputOption::VALUE_NONE, 'Reindex everything?');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManager = $this->getContainer()->get('search.index_manager');
        $doctrine = $this->getContainer()->get('doctrine');
        $entitiesToIndex = $this->getEntitiesToIndex($input, $indexManager);

        foreach ($entitiesToIndex as $entityClassName) {
            $repository = $doctrine->getRepository($entityClassName);
            $manager = $doctrine->getManager();

            $entities = $repository->findAll();

            $indexManager->index($entities, $manager);

            $output->writeln('Indexed <comment>'.count($entities).'</comment> '.$entityClassName);
        }

        $output->writeln('<info>Done!</info>');
    }

    private function getEntitiesToIndex($input, $indexManager)
    {
        if ($input->getOption('all')) {
            return $indexManager->getSearchableEntities();
        }

        return $input->getArgument('entities');
    }
}
