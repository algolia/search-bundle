<?php

namespace Algolia\AlgoliaSearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AlgoliaAlgoliaSearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['application_id'])) {
            $container->setParameter('algolia.application_id', $config['application_id']);
        }

        if (isset($config['api_key'])) {
            $container->setParameter('algolia.api_key', $config['api_key']);
        }

        $container->setParameter('algolia.catch_log_exceptions', $config['catch_log_exceptions']);
        $container->setParameter('algolia.index_name_prefix', $config['index_name_prefix']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function getAlias()
    {
        return 'algolia';
    }
}
