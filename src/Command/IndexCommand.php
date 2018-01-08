<?php

namespace Algolia\SearchBundle\Command;


use Algolia\SearchBundle\IndexManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class IndexCommand extends ContainerAwareCommand
{
    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output, IndexManagerInterface $indexManager)
    {
        $entities = [];
        $indexNames = [];

        if ($indexList = $input->getOption('indices')) {
            $indexNames = explode(',', $indexList);
        }

        $config = $indexManager->getConfiguration();

        if (empty($indexNames)) {
            $indexNames = array_keys($config['indices']);
        }

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
