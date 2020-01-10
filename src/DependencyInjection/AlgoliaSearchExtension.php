<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Algolia\SearchBundle\Engine;
use Algolia\SearchBundle\Services\AlgoliaSearchService;
use Algolia\SearchBundle\Settings\SettingsManager;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 *
 * @internal
 */
final class AlgoliaSearchExtension extends Extension
{
    /**
     * @return void
     *
     * @throws InvalidArgumentException|Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        if (is_null($config['prefix'])) {
            $config['prefix'] = $container->getParameter('kernel.environment') . '_';
        }

        $rootDir = $container->getParameterBag()->get('kernel.project_dir');

        if (is_null($config['settingsDirectory'])) {
            if (Kernel::MAJOR_VERSION >= 3 && !is_dir($rootDir . '/config/')) {
                $config['settingsDirectory'] = '/app/Resources/SearchBundle/settings/';
            } else {
                $config['settingsDirectory'] = '/config/settings/algolia_search/';
            }
        }

        $config['settingsDirectory'] = $rootDir . $config['settingsDirectory'];

        if (count($doctrineSubscribedEvents = $config['doctrineSubscribedEvents']) > 0) {
            $container->getDefinition('search.search_indexer_subscriber')->setArgument(1, $doctrineSubscribedEvents);
        } else {
            $container->removeDefinition('search.search_indexer_subscriber');
        }

        $engineDefinition = new Definition(
            Engine::class,
            [
                new Reference('search.client'),
            ]
        );

        $searchServiceDefinition = (new Definition(
            AlgoliaSearchService::class,
            [
                new Reference($config['serializer']),
                $engineDefinition,
                $config,
            ]
        ));

        $searchServiceDefinitionForAtomicReindex = (clone $searchServiceDefinition)
            ->replaceArgument(2, ['prefix' => 'atomic_temporary_' . uniqid('php_', true) . $config['prefix']] + $config)
        ;

        $settingsManagerDefinition = (new Definition(
            SettingsManager::class,
            [
                new Reference('search.client'),
                $config,
            ]
        ))->setPublic(true);

        $container->setDefinition('search.service', $searchServiceDefinition->setPublic(true));
        $container->setDefinition('search.service_for_atomic_reindex', $searchServiceDefinitionForAtomicReindex);
        $container->setDefinition('search.settings_manager', $settingsManagerDefinition);
    }
}
