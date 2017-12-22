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
        $entities = [];
        $indexNames = [];

        if ($indexList = $input->getOption('indices')) {
            $indexNames = explode(',', $indexList);
        }

        if (empty($indexNames)) {
            return $indexManager->getSearchableEntities();
        }

        $config = $indexManager->getConfiguration();

        foreach ($indexNames as $name) {
            if (isset($config['indices'][$name])) {
                $entities[$name] = $config['indices'][$name]['class'];
            } else {
                $output->writeln('<comment>No index named <info>'.$name.'</info> was found. Check you configuration.</comment>');
            }
        }

        return $entities;
    }
}
