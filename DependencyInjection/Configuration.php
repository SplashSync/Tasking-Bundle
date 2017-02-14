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
                // COMMON Parameters
                //====================================================================//
                ->scalarNode('environement')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Specify the environement to use for running background tasks.')
                ->end()
                ->integerNode('refresh_delay')
                    ->defaultValue(3)
                    ->info('Time in seconds between two status refresh of a worker.')
                ->end()
                ->integerNode('watchdog_delay')
                    ->defaultValue(30)
                    ->info('Max. Time in seconds between two status refresh of a worker. If exeeded, worker is considered as faulty and restarted.')
                ->end()
                ->booleanNode('multiserver')
                    ->defaultValue(False)
                    ->info('Enable Multi-Server mode. Allow ativation of Workers on a Pool of Servers')
                ->end()
                ->scalarNode('multiserver_path')
                    ->defaultValue("")
                    ->info('Aditionnal Path for Multi-Server mode. ')
                ->end()
                
                //====================================================================//
                // SUPERVISOR Parameters
                //====================================================================//
                ->arrayNode('supervisor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_age')
                            ->defaultValue(3600)
                            ->info('Max. Age for a Supervisor Process in seconds. Supervisor Worker will stop after this delay.')
                        ->end()
                        ->scalarNode('refresh_delay')
                            ->defaultValue(100)
                            ->info('Delay between two Supervisor Worker Status checks in MilliSeconds.')
                        ->end()
                        ->integerNode('max_workers')
                            ->info('Number of active worker on same machine.')
                            ->defaultValue(5)
                        ->end()
                        ->integerNode('max_memory')
                            ->info('Maximum Memory usage for Supervisor. Exit when reached.')
                            ->defaultValue(200)
                        ->end()
                    ->end()
                ->end()
                //====================================================================//
                // WORKERS Parameters
                //====================================================================//
                ->arrayNode('workers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_tasks')
                            ->defaultValue(100)
                            ->info('Maximum task executed by a Worker. Restart when reached.')
                        ->end()
                        ->integerNode('max_age')
                            ->defaultValue(120)
                            ->info('Maximum lifetime for a Worker. Restart when reached.')
                        ->end()
                        ->integerNode('max_memory')
                            ->info('Maximum Memory usage for a Worker. Restart when reached.')
                            ->defaultValue(200)
                        ->end()
                    ->end()
                ->end()
                //====================================================================//
                // TASKS Parameters
                //====================================================================//
                ->arrayNode('tasks')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_age')
                            ->defaultValue(180)
                            ->info('Delay before a Completed Task is Deleted from Database.')
                        ->end()
                        ->integerNode('try_count')
                            ->info('Number of failure of a Task before considering it finished.')
                            ->defaultValue(5)
                        ->end()
                        ->integerNode('try_delay')
                            ->defaultValue(120)
                            ->info('Delay before restarting a task that fails. In Seconds')
                        ->end()
                        ->integerNode('error_delay')
                            ->defaultValue(40)
                            ->info('Delay before considering a running task as failled. In Seconds')
                        ->end()
                    ->end()
                ->end()
                //====================================================================//
                // STATIC TASKS Parameters
                //====================================================================//
                ->arrayNode('static')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('frequency')->isRequired()->min(1)->end()
                            ->scalarNode('token')->defaultValue(Null)->end()
                            ->arrayNode('inputs') 
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()                

            ->end()
        ;
        return $treeBuilder;
    }
}
