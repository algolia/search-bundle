<?php

namespace Algolia\SearchBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchClearCommand extends IndexCommand
{
    protected function configure()
    {
        $this
            ->setName('search:clear')
            ->setDescription('Clear index (remove all)')
            ->addArgument('indexNames', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Name of the index to clear (without prefix)')
            ->addOption('all', false, InputOption::VALUE_NONE, 'Clear all indices');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManager = $this->getContainer()->get('search.index_manager');
        $indexToClear = $this->getEntitiesFromArgs($input, $output, $indexManager);

        foreach ($indexToClear as $indexName) {
            $indexManager->clear($indexName);

            $output->writeln('Cleared <comment>'.$indexName.'</comment> index');
        }

        $output->writeln('<info>Done!</info>');
    }
}
