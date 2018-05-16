<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Algolia\SearchBundle\IndexManager;
use Algolia\SearchBundle\Settings\AlgoliaSettingsManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Kernel;

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

        if (is_null($config['prefix'])) {
            $config['prefix'] = $container->getParameter("kernel.environment").'_';
        }

        $rootDir = $container->getParameterBag()->get('kernel.project_dir');

        if (is_null($config['settingsDirectory'])) {
            if (3 == Kernel::MAJOR_VERSION && !is_dir($rootDir.'/config/')) {
                $config['settingsDirectory'] = '/app/Resources/SearchBundle/settings/';
            } else {
                $config['settingsDirectory'] = '/config/settings/algolia_search/';
            }
        }

        $config['settingsDirectory'] = $rootDir.$config['settingsDirectory'];

        if (count($doctrineSubscribedEvents = $config['doctrineSubscribedEvents']) > 0) {
            $container->getDefinition('search.search_indexer_subscriber')->setArgument(1, $doctrineSubscribedEvents);
        } else {
            $container->removeDefinition('search.search_indexer_subscriber');
        }

        $indexManagerDefinition = (new Definition(
            IndexManager::class,
            [
                new Reference($config['serializer']),
                new Reference('search.engine'),
                $config
            ]
        ))->setPublic(true);

        $settingsManagerDefinition = (new Definition(
            AlgoliaSettingsManager::class,
            [
                new Reference('algolia_client'),
                $config
            ]
        ))->setPublic(true);

        $container->setDefinition('search.index_manager', $indexManagerDefinition);
        $container->setDefinition('search.settings_manager', $settingsManagerDefinition);
    }
}
