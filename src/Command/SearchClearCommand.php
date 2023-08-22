<?php

namespace Algolia\SearchBundle\Command;

use Algolia\AlgoliaSearch\Response\IndexingResponse;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'search:clear',
    description: 'Clear index (remove all data but keep index and settings)'
)]
final class SearchClearCommand extends IndexCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexToClear = $this->getEntitiesFromArgs($input, $output);

        foreach ($indexToClear as $indexName => $className) {
            $success = $this->searchService->clear($className);

            if ($success instanceof IndexingResponse) {
                $output->writeln('Cleared <info>' . $indexName . '</info> index of <comment>' . $className . '</comment> ');
            } else {
                $output->writeln('<error>Index <info>' . $indexName . '</info>  couldn\'t be cleared</error>');
            }
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }
}
