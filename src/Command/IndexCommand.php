<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\IndexManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use const E_USER_DEPRECATED;
use function trigger_error;

abstract class IndexCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $indexManager;

    public function __construct(IndexManagerInterface $indexManager)
    {
        $this->indexManager = $indexManager;

        parent::__construct();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        @trigger_error(
            sprintf('The %s method is deprecated and should not be used. Please wire your dependencies explicitly.', __METHOD__),
            E_USER_DEPRECATED
        );

        return $this->container;
    }

    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output)
    {
        $entities = [];
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
                $output->writeln('<comment>No index named <info>'.$name.'</info> was found. Check you configuration.</comment>');
            }
        }

        return $entities;
    }
}
