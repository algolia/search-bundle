<?php

namespace Algolia\SearchBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SearchSettingsBackupCommand extends SearchSettingsCommand
{
    protected static $defaultName = 'search:settings:backup';

    protected function configure()
    {
        $this
            ->setDescription('Backup search engine settings into your project')
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addArgument(
                'extra',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Check your engine documentation for available options'
            );
    }

    protected function handle($params)
    {
        return $this->settingsManager->backup($params);
    }
}
