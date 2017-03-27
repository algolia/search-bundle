<?php

namespace Algolia\AlgoliaSearchBundle\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AlgoliaCommand extends ContainerAwareCommand
{
    /** @var ObjectManager */
    private $objectManager;

    protected function configure()
    {
        $this
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command')
            ->addOption('dm', null, InputOption::VALUE_OPTIONAL, 'The document manager to use for this command.')
        ;
    }

    /**
     * Checks input options and extracts the object manager to be used
     *
     * If the dm option was given, it will use the document manager specified, or
     * the default one if no value was given. In other cases, it will try to fetch
     * the entity manager specified, or the default one if none was given.
     * Specifying both em and dm options will cause an error.
     *
     * @param InputInterface $input
     *
     * @throws LogicException
     */
    protected function setObjectManagerFromInput(InputInterface $input)
    {
        $hasOrm = $this->getContainer()->has('doctrine');
        $hasOdm = $this->getContainer()->has('doctrine_mongodb');

        if ($hasOrm && $hasOdm) {
            if ($input->getOption('em') && $input->getOption('dm')) {
                throw new LogicException('Cannot set both em and dm options at the same time.');
            }

            if ($input->getOption('dm')) {
                $this->objectManager = $this->getDocumentManager($input->getOption('dm'));
            } else {
                $this->objectManager = $this->getEntityManager($input->getOption('em'));
            }
        } elseif ($hasOdm) {
            $this->objectManager = $this->getDocumentManager($input->getOption('dm'));
        } else {
            $this->objectManager = $this->getEntityManager($input->getOption('em'));
        }

        if (! $this->objectManager) {
            throw new LogicException('Could not find an object manager. Do you have Doctrine ORM or ODM installed?');
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Sets the object manager to the document manager with the specified name
     *
     * @param string $managerName
     * @return ObjectManager
     */
    private function getDocumentManager($managerName)
    {
        return $this->objectManager = $this
            ->getContainer()
            ->get('doctrine_mongodb')
            ->getManager($managerName);
    }

    /**
     * Sets the object manager to the entity manager with the specified name
     *
     * @param string $managerName
     * @return ObjectManager
     */
    private function getEntityManager($managerName)
    {
        return $this->objectManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager($managerName);
    }
}
