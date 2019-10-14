<?php

namespace Algolia\SearchBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
final class SearchSettingsPushCommand extends SearchSettingsCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'search:settings:push';

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Push settings from your project to the search engine')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    /**
     * @param array<string, array> $params
     *
     * @return array<int, string>
     */
    protected function handle($params)
    {
        return $this->settingsManager->push($params);
    }
}
