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
        if ($indexList = $input->getOption('indices')) {
            $indexList = explode(',', $indexList);
        }

        $params = [
            'indices' => (array) $indexList,
            'extra' => $input->getArgument('extra'),
        ];

        $settingsManager = $this->getContainer()->get('search.settings_manager');

        $message = $this->handle($settingsManager, $params);

        $output->writeln($message);
    }

    abstract protected function handle(SettingsManagerInterface $settingsManager, $params);
}
