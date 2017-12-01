<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Algolia\SearchBundle\IndexManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AlgoliaSearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $prefix = $config['prefix'];
        if (is_null($prefix)) {
            $prefix = $container->getParameter("kernel.environment").'_';
        }

        $container->setParameter('algolia_search.doctrineSubscribedEvents', $config['doctrineSubscribedEvents']);

        $indexManagerDefinition = (new Definition(
            IndexManager::class,
            [
                new Reference('serializer'),
                new Reference('search.engine'),
                $config['indices'],
                $prefix,
                $config['nbResults']
            ]
        ))->setPublic(true);

        $container->setDefinition('search.index_manager', $indexManagerDefinition);
    }
}
