<?php

namespace Splash\Tasking\DependencyInjection;

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
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('splash_tasking');

        $rootNode
            ->children()
                //====================================================================//
                // SUPERVISOR Parameters
                //====================================================================//
                ->arrayNode('supervisor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_age')->defaultValue(3600)->end()
                        ->scalarNode('refresh_delay')->defaultValue(1E5)->end()
                        ->integerNode('max_workers')->defaultValue(5)->end()
                    ->end()
                ->end()
                //====================================================================//
                // WORKERS Parameters
                //====================================================================//
                ->arrayNode('workers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_tasks')->defaultValue(100)->end()
                        ->integerNode('max_age')->defaultValue(120)->end()
                        ->integerNode('max_memory')->defaultValue(200)->end()
                    ->end()
                ->end()
                //====================================================================//
                // TASKS Parameters
                //====================================================================//
                ->arrayNode('tasks')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_age')->defaultValue(60)->end()
                        ->integerNode('try_count')->defaultValue(5)->end()
                        ->integerNode('try_delay')->defaultValue(1)->end()
                        ->integerNode('error_delay')->defaultValue(1)->end()
                        ->scalarNode('environement')->defaultValue("prod")->end()
                    ->end()
                ->end()
                //====================================================================//
                // STATIC TASKS Parameters
                //====================================================================//
                ->arrayNode('static')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->integerNode('frequency')->defaultValue(24)->end()
                            ->booleanNode('enabled')->defaultValue(TRUE)->end()
                            ->variableNode('inputs')->end()
                        ->end()
                    ->end()
                ->end()                

            ->end()
        ;
        return $treeBuilder;
    }
}
