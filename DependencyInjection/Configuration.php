<?php

namespace Synth\MemcacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('synth_memcache');

        $rootNode->children()
            ->scalarNode('memcache_class')
                ->cannotBeEmpty()
                ->defaultValue("\Memcache")
                ->end()
            ->scalarNode('key_prefix')
                ->defaultValue("")
                ->end()
            ->arrayNode('servers')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('port')->defaultValue(11211)->cannotBeEmpty()->end()
                        ->scalarNode('weight')->defaultValue(0)->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
