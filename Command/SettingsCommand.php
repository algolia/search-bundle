<?php

namespace Algolia\AlgoliaSearchBundle\Command;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SettingsCommand extends AlgoliaCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('algolia:settings')
            ->setDescription('Push the Algolia index settings defined in your project to the Algolia servers.')
            ->addOption('push', 'p', InputOption::VALUE_NONE, 'Push the local settings to the remote Algolia servers.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not ask for confirmation.')
        ;
    }

    protected function getObjectClasses()
    {
        $metaData = $this->getObjectManager()
            ->getMetadataFactory()
            ->getAllMetadata();

        return array_map(
            function (ClassMetadata $data) {
                return $data->getName();
            },
            $metaData
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setObjectManagerFromInput($input);

        $operation = 'diff';

        if ($input->getOption('push')) {
            $operation = 'push';
        }

        $output->writeln('<comment>Computing differences between local and remote indexes...</comment>');

        $entityClasses = $this->getObjectClasses();

        $indexer = $this->getContainer()->get('algolia.indexer');

        foreach ($entityClasses as $className) {
            $indexer->discoverEntity($className, $this->getObjectManager());
        }

        $localIndexSettings = [];

        foreach ($indexer->getIndexSettings() as $class => $settings) {
            if ($settings) {
                $indexName = $indexer->getAlgoliaIndexName($class);
                $algoliaSettings = $settings->getIndex()->getAlgoliaSettings();

                $localIndexSettings[$indexName] = $algoliaSettings;
            }
        }

        $dirty = [];

        foreach ($localIndexSettings as $indexName => $localSettings) {
            if (empty($localSettings)) {
                // Cannot update settings if they're empty.
                continue;
            }

            try {
                $currentSettings = $indexer->getIndex($indexName)->getSettings();
            } catch (\Exception $e) {
                $currentSettings = null;
            }

            if ($currentSettings === null) {
                $dirty[$indexName] = true;
                $output->writeln("Found a new local index <fg=green>$indexName</fg=green>.");
            } else {
                foreach ($localSettings as $parameter => $localValue) {
                    if (!isset($currentSettings[$parameter])) {
                        $dirty[$indexName] = true;
                        $output->writeln("Parameter <fg=green>$parameter</fg=green> is new in the local definition of <fg=yellow>$indexName</fg=yellow>.");
                        $output->writeln("New value for $parameter:");
                        $output->writeln('<fg=yellow>'.(is_array($localValue) ? json_encode($localValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $localValue).'</fg=yellow>');
                        $output->writeln('');
                    } else {
                        $remoteValue = $currentSettings[$parameter];
                        if ($remoteValue !== $localValue) {
                            $dirty[$indexName] = true;
                            $output->writeln("\nParameter <fg=yellow>$parameter</fg=yellow> is different in the local definition of <fg=yellow>$indexName</fg=yellow>.");
                            $output->writeln("Local $parameter:");
                            $output->writeln('<fg=yellow>'.(is_array($localValue) ? json_encode($localValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $localValue).'</fg=yellow>');
                            $output->writeln("Remote $parameter:");
                            $output->writeln('<fg=blue>'.(is_array($remoteValue) ? json_encode($remoteValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $remoteValue).'</fg=blue>');
                            $output->writeln('');
                        }
                    }
                }
            }
        }

        if (count($dirty) === 0) {
            $output->writeln("<fg=green>Your local index settings seem to be in sync with the Algolia servers!</fg=green>");
        } else {
            $output->writeln("\n<fg=cyan>We found ".count($dirty)." index(es) that may need updating.</fg=cyan>");
            if ('diff' === $operation) {
                $output->writeln('<comment>Run this command with --push if you want to push the new local settings to Algolia.</comment>');
            } elseif ('push' === $operation) {
                $output->writeln('');
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Are you sure you want to update the remote Algolia settings? This operation cannot be undone! (y/N)', false);
                if ($input->getOption('force') || $helper->ask($input, $output, $question)) {
                    foreach ($dirty as $indexName => $unused) {
                        $indexer->setIndexSettings($indexName, $localIndexSettings[$indexName], ['adaptIndexName' => false]);
                    }
                    $output->writeln("\n<fg=green>Done updating the settings! (but the tasks may not have completed on Algolia's side yet).</fg=green>");
                } else {
                    $output->writeln("\nOk, changing nothing.");
                }
            }
        }

        $indexer->waitForAlgoliaTasks();
    }
}
