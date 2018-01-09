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
            ->setDescription('Clear index (remove all data but keep index and settings)')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexToClear = $this->getEntitiesFromArgs($input, $output);

        foreach ($indexToClear as $indexName => $className) {
            $this->indexManager->clear($className);

            $output->writeln('Cleared <info>'.$indexName.'</info> index of <comment>'.$className.'</comment> ');
        }

        $output->writeln('<info>Done!</info>');
    }
}
