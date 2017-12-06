<?php

namespace Algolia\SearchBundle\Command;


use Algolia\SearchBundle\Settings\SettingsManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SearchSettingsCommand extends ContainerAwareCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($indexList = (array) $input->getOption('indices')) {
            $indexList = explode(',', $indexList);
        }

        $params = [
            'indices' => $indexList,
            'extra' => $input->getArgument('extra'),
        ];

        $projectDir = $this->getContainer()->get('kernel')->getProjectDir();
        $settingsDir = $projectDir.'/config/settings/algolia_search';

        $settingsManager = $this->getContainer()->get('search.settings_manager');

        $message = $this->handle($settingsManager, $settingsDir, $params);

        $output->writeln($message);
    }

    abstract protected function handle(SettingsManagerInterface $settingsManager, $settingsDir, $params);
}
