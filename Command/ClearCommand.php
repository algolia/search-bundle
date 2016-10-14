<?php

namespace Algolia\AlgoliaSearchBundle\Command;

use Algolia\AlgoliaSearchBundle\Exception\NotAnAlgoliaEntity;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends AlgoliaCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('algolia:clean')
            ->setDescription('Clear the index related to an entity')
            ->addArgument('entityName', InputArgument::OPTIONAL, 'Which entity index do you want to clear? If not set, all is assumed.')
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

        $toClear = [];

        if ($input->getArgument('entityName')) {
            $filter = $this->getObjectManager()->getRepository($input->getArgument('entityName'))->getClassName();
        } else {
            $filter = null;
        }

        foreach ($this->getObjectClasses() as $class) {
            if (!$filter || $class === $filter) {
                $toClear[] = $class;
            }
        }

        $nCleared = 0;
        foreach ($toClear as $className) {
            try {
                $this->clear($className);
                $nCleared++;
            } catch (NotAnAlgoliaEntity $e) {
                $output->writeln("<info>Skipped $className which isn't an entity to clear.</info>");
            }
        }

        switch ($nCleared) {
            case 0:
                $output->writeln('No entity cleared');
                break;
            case 1:
                $output->writeln('<info>1</info> entity cleared');
                break;
            default:
                $output->writeln(sprintf('<info>%s</info> entities cleared', $nCleared));
                break;
        }

        return 0;
    }

    public function clear($className)
    {
        $reIndexer = $this->getContainer()->get('algolia.indexer')->getManualIndexer($this->getObjectManager());
        $reIndexer->clear($className);
    }
}
