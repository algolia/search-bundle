<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\IndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
abstract class IndexCommand extends Command
{
    protected $indexManager;

    public function __construct(IndexManager $indexManager)
    {
        $this->indexManager = $indexManager;

        parent::__construct();
    }

    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output)
    {
        $entities   = [];
        $indexNames = [];

        if ($indexList = $input->getOption('indices')) {
            $indexNames = explode(',', $indexList);
        }

        $config = $this->indexManager->getConfiguration();

        if (empty($indexNames)) {
            $indexNames = array_keys($config['indices']);
        }

        foreach ($indexNames as $name) {
            if (isset($config['indices'][$name])) {
                $entities[$name] = $config['indices'][$name]['class'];
            } else {
                $output->writeln('<comment>No index named <info>' . $name . '</info> was found. Check you configuration.</comment>');
            }
        }

        return $entities;
    }
}
