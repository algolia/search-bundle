<?php

namespace Algolia\AlgoliaSearchBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('algolia:clean')
            ->setDescription('Clear the index related to an entity')
            ->addArgument('entityName', InputArgument::OPTIONAL, 'Which entity index do you want to clear? If not set, all is assumed.')
        ;
    }

    protected function getEntityManager()
    {
        return $this
            ->getContainer()
            ->get('doctrine.orm.entity_manager');
    }

    protected function getEntityClasses()
    {
        $metaData = $this->getEntityManager()
            ->getMetadataFactory()
            ->getAllMetaData();

        return array_map(function ($data) {
            return $data->getName();
        }, $metaData);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $toReindex = [];

        if ($input->hasArgument('entityName')) {
            $filter = $this->getEntityManager()->getRepository($input->getArgument('entityName'))->getClassName();
        } else {
            $filter = null;
        }

        foreach ($this->getEntityClasses() as $class) {
            if (!$filter || $class === $filter) {
                $toReindex[] = $class;
            }
        }

        $nIndexed = 0;
        foreach ($toReindex as $className) {
            $nIndexed += $this->clear($className);
        }

        return $nIndexed;
    }

    public function clear($className)
    {
        $reIndexer = $this->getContainer()->get('algolia.indexer')->getManualIndexer($this->getEntityManager());

        return $reIndexer->clear($className);
    }
}
