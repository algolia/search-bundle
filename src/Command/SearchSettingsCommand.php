<?php

namespace Algolia\SearchBundle\Command;

use Algolia\SearchBundle\Settings\AlgoliaSettingsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SearchSettingsCommand.
 *
 * @internal
 */
abstract class SearchSettingsCommand extends Command
{
    protected $settingsManager;

    public function __construct(AlgoliaSettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;

        parent::__construct();
    }

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
    }

    abstract protected function handle($params);
}
