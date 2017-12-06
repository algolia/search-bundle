<?php

namespace Algolia\SearchBundle\Command;


use Algolia\SearchBundle\Settings\SettingsManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SearchSettingsPushCommand extends SearchSettingsCommand
{
    protected function configure()
    {
        $this
            ->setName('search:settings:push')
            ->setDescription('Push settings from your project to the search engine')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    protected function handle(SettingsManagerInterface $settingsManager, $settingsDir, $params)
    {
        return $settingsManager->push($settingsDir, $params);
    }
}
