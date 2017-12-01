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
                        ->end()
                    ->end()
                ->end() // indices
            ->end();

        return $treeBuilder;
    }
}
