<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\Settings\AlgoliaSettingsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
abstract class SearchSettingsCommand extends Command
{
    /**
     * @var AlgoliaSettingsManager
     */
    protected $settingsManager;

    /**
     * @param AlgoliaSettingsManager $settingsManager
     */
    public function __construct(AlgoliaSettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;

        parent::__construct();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($indexList = $input->getOption('indices')) {
            $indexList = explode(',', $indexList);
        }

        $params = [
            'indices' => (array) $indexList,
            'extra'   => $input->getArgument('extra'),
        ];

        $message = $this->handle($params);

        $output->writeln($message);

        return null;
    }

    /**
     * @param array<string, array> $params
     *
     * @return array<int, string>
     */
    abstract protected function handle($params);
}
