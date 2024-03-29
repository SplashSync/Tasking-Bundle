<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var NodeDefinition
     */
    private NodeDefinition $rootNode;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('splash_tasking');
        $this->rootNode = $treeBuilder->getRootNode();

        $this->configureCommonParameters();
        $this->configureServerParameters();
        $this->configureSupervisorParameters();
        $this->configureWorkersParameters();
        $this->configureTasksParameters();

        return $treeBuilder;
    }

    /**
     * Add Common Parameters To Configuration
     *
     * @return void
     */
    private function configureCommonParameters(): void
    {
        // @phpstan-ignore-next-line
        $this->rootNode
            ->children()
            //====================================================================//
            // COMMON Parameters
            //====================================================================//
            ->scalarNode('environement')
            ->defaultValue('prod')
            ->cannotBeEmpty()
            ->info('Specify the environnement to use for running background tasks.')
            ->end()
            ->scalarNode('entity_manager')
            ->defaultValue('default')
            ->cannotBeEmpty()
            ->info('Specify the Doctrine Entity Manager to use for storing tasks.')
            ->end()
            ->integerNode('refresh_delay')
            ->defaultValue(3)
            ->info('Time in seconds between two status refresh of a worker.')
            ->end()
            ->integerNode('watchdog_delay')
            ->defaultValue(30)
            ->info(
                'Max. Time in seconds between two status refresh of a worker. 
                If exceeded, worker is considered as faulty and restarted.'
            )
            ->end()
            ->booleanNode('multiserver')
            ->defaultValue(false)
            ->info('Enable Multi-Server mode. Allow activation of Workers on a Pool of Servers')
            ->end()
            ->scalarNode('multiserver_path')
            ->defaultValue("/tasking/start")
            ->info('Additional Path for Multi-Server mode.')
            ->end()
            ->end()
        ;
    }

    /**
     * Add Server Parameters To Configuration
     *
     * @return $this
     */
    private function configureServerParameters(): self
    {
        // @phpstan-ignore-next-line
        $this->rootNode
            ->children()
            //====================================================================//
            // SERVER Parameters
            //====================================================================//
            ->arrayNode('server')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('force_crontab')
            ->defaultValue(false)
            ->info('Enable to force setup of users crontab. When disabled you need to manage crontab manually')
            ->end()
            ->scalarNode('php_version')
            ->defaultValue("php")
            ->info('PHP Cli line to use for starting bash commands.')
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $this;
    }

    /**
     * Add Supervisor Parameters To Configuration
     *
     * @return void
     */
    private function configureSupervisorParameters(): void
    {
        // @phpstan-ignore-next-line
        $this->rootNode
            ->children()
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
            ->defaultValue(500)
            ->info('Delay between two Supervisor Worker Status checks in MilliSeconds.')
            ->end()
            ->integerNode('max_workers')
            ->info('Number of active worker on same machine.')
            ->defaultValue(3)
            ->end()
            ->integerNode('max_memory')
            ->info('Maximum Memory usage for Supervisor. Exit when reached.')
            ->defaultValue(100)
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    /**
     * Add Workers Parameters To Configuration
     *
     * @return void
     */
    private function configureWorkersParameters(): void
    {
        // @phpstan-ignore-next-line
        $this->rootNode
            ->children()
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
            ->end()
        ;
    }

    /**
     * Add Tasks Parameters To Configuration
     *
     * @return void
     */
    private function configureTasksParameters(): void
    {
        // @phpstan-ignore-next-line
        $this->rootNode
            ->children()
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
            ->end()
            ->end()
            ->end()
        ;
    }
}
