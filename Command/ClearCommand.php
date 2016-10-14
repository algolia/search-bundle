<?php

namespace Algolia\AlgoliaSearchBundle\Command;

use Algolia\AlgoliaSearchBundle\Exception\NotAnAlgoliaEntity;
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
            ->addOption('skip-non-algolia-entities', null, InputOption::VALUE_NONE, 'Omit errors for all non Algolia entities.')
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

        if ($input->getArgument('entityName')) {
            $filter = $this->getEntityManager()->getRepository($input->getArgument('entityName'))->getClassName();
        } else {
            $filter = null;
        }

        foreach ($this->getEntityClasses() as $class) {
            if (!$filter || $class === $filter) {
                $toReindex[] = $class;
            }
        }

        $skipNonAlgoliaEntities = $input->hasOption('skip-non-algolia-entities');

        $nIndexed = 0;
        foreach ($toReindex as $className) {
            $this->clear($className, $skipNonAlgoliaEntities) ? $nIndexed++ : $nIndexed;
        }

        switch ($nIndexed) {
            case 0:
                $output->writeln('No entity cleared');
                break;
            case 1:
                $output->writeln('<info>1</info> entity cleared');
                break;
            default:
                $output->writeln(sprintf('<info>%s</info> entities cleared', $nIndexed));
                break;
        }
    }

    public function clear($className, $skipNonAlgoliaEntities = false)
    {
        $reIndexer = $this->getContainer()->get('algolia.indexer')->getManualIndexer($this->getEntityManager());

        try {
            $reIndexer->clear($className);

            return true;
        } catch (NotAnAlgoliaEntity $e) {
            if (! $skipNonAlgoliaEntities) {
                throw $e;
            }
        }

        return false;
    }
}
