<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\SearchServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
abstract class IndexCommand extends Command
{
    /**
     * @var SearchServiceInterface
     */
    protected $searchService;

    /**
     * @param SearchServiceInterface $searchService
     */
    public function __construct(SearchServiceInterface $searchService)
    {
        $this->searchService = $searchService;

        parent::__construct();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array<string, string>
     */
    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output)
    {
        $entities   = [];
        $indexNames = [];

        if ($indexList = $input->getOption('indices')) {
            $indexNames = explode(',', $indexList);
        }

        $config = $this->searchService->getConfiguration();

        if (count($indexNames) === 0) {
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
