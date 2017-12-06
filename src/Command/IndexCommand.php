<?php

namespace Algolia\SearchBundle\Command;


use Algolia\SearchBundle\IndexingManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class IndexCommand extends ContainerAwareCommand
{
    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output, IndexingManagerInterface $indexManager)
    {
        if ($input->getOption('all')) {
            return $indexManager->getSearchableEntities();
        }

        $entities = [];
        $indexNames = $input->getArgument('indexNames');
        $config = $indexManager->getConfiguration();

        foreach ($indexNames as $name) {
            if (isset($config['indices'][$name])) {
                $entities[] = $config['indices'][$name]['class'];
            } else {
                $output->writeln('<comment>No index named <info>'.$name.'</info> was found. Check you configuration.</comment>');
            }
        }

        return $entities;
    }
}
