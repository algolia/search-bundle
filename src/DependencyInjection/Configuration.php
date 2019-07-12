<?php

namespace Algolia\SearchBundle\DependencyInjection;

use Algolia\SearchBundle\Searchable;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function method_exists;

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
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('algolia_search');
            $rootNode    = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode    = $treeBuilder->root('algolia_search');
        }

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
                ->scalarNode('serializer')
                    ->defaultValue('serializer')
                ->end()
                ->arrayNode('indices')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifTrue(function($v) {
                                return !empty($v['serializer_groups']) &&
                                       (!isset($v['enable_serializer_groups']) || !$v['enable_serializer_groups']);
                            })
                            ->thenInvalid('In order to specify "serializer_groups" you need to enable "enable_serializer_groups"')
                        ->end()
                        ->children()
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('enable_serializer_groups')
                                ->info(
                                    'When set to true, it will call normalize method with an extra groups ' .
                                    'defined in "serializer_groups" (Searchable::NORMALIZATION_GROUP by default)'
                                )
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('serializer_groups')
                                ->info('List of serializer groups to use while serializing. This option requires "enable_serializer_groups" set to true.')
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->scalarPrototype()->end()
                                ->defaultValue([Searchable::NORMALIZATION_GROUP])
                            ->end()
                            ->scalarNode('index_if')
                                ->info('Property accessor path (like method or property name) used to decide if an entry should be indexed.')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end() // indices
            ->end();

        return $treeBuilder;
    }
}
