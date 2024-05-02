<?php

namespace Algolia\SearchBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @internal
 */
#[AsCommand(name: 'search:settings:backup')]
final class SearchSettingsBackupCommand extends SearchSettingsCommand
{
    /**
     * @return void
     */
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

    /**
     * @param array<string, array> $params
     *
     * @return array<int, string>
     */
    protected function handle($params)
    {
        return $this->settingsManager->backup($params);
    }
}
