<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('algolia_search');

        $rootNode
            ->children()
                ->scalarNode('prefix')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('nbResults')
                    ->defaultValue(20)
                ->end()
                ->scalarNode('settingsDirectory')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('batchSize')
                    ->defaultValue(500)
                ->end()
                ->arrayNode('doctrineSubscribedEvents')
                    ->prototype('scalar')->end()
                    ->defaultValue(['postPersist', 'postUpdate', 'preRemove'])
                ->end()
                ->arrayNode('indices')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('enable_serializer_groups')
                                ->info('When set to true, it will call normalize method with an extra groups parameter "groups" => [Searchable::NORMALIZATION_GROUP]')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('index_if')
                                ->info('Name of the method in the entity used to decide if an entry should be indexed or not.')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end() // indices
            ->end();

        return $treeBuilder;
    }
}
