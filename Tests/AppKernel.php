<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Algolia\AlgoliaSearchBundle\AlgoliaAlgoliaSearchBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}
