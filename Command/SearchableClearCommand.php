<?php
/**
 * Created by PhpStorm.
 * User: julien
 * Date: 03/11/2017
 * Time: 17:14
 */

namespace Algolia\SearchBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchableClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('searchable:clear')
            ->setDescription('Clear index (remove all)')
            ->addArgument('indexNames', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Index to clear')
            ->addOption('all', false, InputOption::VALUE_NONE, 'Reindex everything?');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexManager = $this->getContainer()->get('search.index_manager');
        $indexToClear = $this->getIndexToClear($input, $indexManager);

        foreach ($indexToClear as $indexName) {
            $indexManager->clear($indexName);

            $output->writeln('Cleared <comment>'.$indexName.'</comment> index');
        }

        $output->writeln('<info>Done!</info>');
    }

    private function getIndexToClear($input, $indexManager)
    {
        if ($input->getOption('all')) {
            return array_keys($indexManager->getIndexConfiguration());
        }

        return $input->getArgument('indexNames');
    }
}
