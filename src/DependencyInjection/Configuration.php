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

namespace BadPixxel\Tasking\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\Assert\Assert;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var ArrayNodeDefinition
     */
    private ArrayNodeDefinition $rootNode;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('badpixxel_tasking');
        //====================================================================//
        // Root Node Type Hints Differs with Symfony Versions
        /** @var NodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        Assert::isInstanceOf($rootNode, ArrayNodeDefinition::class);
        $this->rootNode = $rootNode;

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
        //====================================================================//
        // COMMON Parameters
        //====================================================================//
        $children = $this->rootNode->children();
        $children->scalarNode('environment')
            ->defaultValue('prod')
            ->cannotBeEmpty()
            ->info('Specify the environnement to use for running background tasks.')
        ;
        $children->scalarNode('entity_manager')
            ->defaultValue('default')
            ->cannotBeEmpty()
            ->info('Specify the Doctrine Entity Manager to use for storing tasks.')
        ;
        $children->integerNode('refresh_delay')
            ->defaultValue(3)
            ->info('Time in seconds between two status refresh of a worker.')
        ;
        $children->integerNode('watchdog_delay')
            ->defaultValue(30)
            ->info(
                'Max. Time in seconds between two status refresh of a worker.
                If exceeded, worker is considered as faulty and restarted.'
            )
        ;
        $children->booleanNode('multiserver')
            ->defaultValue(false)
            ->info('Enable Multi-Server mode. Allow activation of Workers on a Pool of Servers')
        ;
        $children->scalarNode('multiserver_path')
            ->defaultValue("/tasking/start")
            ->info('Additional Path for Multi-Server mode.')
        ;
    }

    /**
     * Add Server Parameters To Configuration
     *
     * @return void
     */
    private function configureServerParameters(): void
    {
        //====================================================================//
        // SERVER Parameters
        //====================================================================//
        $children = $this->rootNode->children()
            ->arrayNode('server')
            ->addDefaultsIfNotSet()
            ->children()
        ;
        $children->booleanNode('force_crontab')
            ->defaultValue(false)
            ->info('Enable to force setup of users crontab. When disabled you need to manage crontab manually')
        ;
        $children->scalarNode('php_version')
            ->defaultValue("php")
            ->info('PHP Cli line to use for starting bash commands.')
        ;
    }

    /**
     * Add Supervisor Parameters To Configuration
     *
     * @return void
     */
    private function configureSupervisorParameters(): void
    {
        //====================================================================//
        // SUPERVISOR Parameters
        //====================================================================//
        $children = $this->rootNode->children()
            ->arrayNode('supervisor')
            ->addDefaultsIfNotSet()
            ->children()
        ;
        $children->integerNode('max_age')
            ->defaultValue(3600)
            ->info('Max. Age for a Supervisor Process in seconds. Supervisor Worker will stop after this delay.')
        ;
        $children->scalarNode('refresh_delay')
            ->defaultValue(500)
            ->info('Delay between two Supervisor Worker Status checks in MilliSeconds.')
        ;
        $children->integerNode('max_workers')
            ->info('Number of active worker on same machine.')
            ->defaultValue(3)
        ;
        $children->integerNode('max_memory')
            ->info('Maximum Memory usage for Supervisor. Exit when reached.')
            ->defaultValue(100)
        ;
    }

    /**
     * Add Workers Parameters To Configuration
     *
     * @return void
     */
    private function configureWorkersParameters(): void
    {
        //====================================================================//
        // WORKERS Parameters
        //====================================================================//
        $children = $this->rootNode->children()
            ->arrayNode('workers')
            ->addDefaultsIfNotSet()
            ->children()
        ;
        $children->integerNode('max_tasks')
            ->defaultValue(100)
            ->info('Maximum task executed by a Worker. Restart when reached.')
        ;
        $children->integerNode('max_age')
            ->defaultValue(120)
            ->info('Maximum lifetime for a Worker. Restart when reached.')
        ;
        $children->integerNode('max_memory')
            ->info('Maximum Memory usage for a Worker. Restart when reached.')
            ->defaultValue(200)
        ;
    }

    /**
     * Add Tasks Parameters To Configuration
     *
     * @return void
     */
    private function configureTasksParameters(): void
    {
        //====================================================================//
        // TASKS Parameters
        //====================================================================//
        $children = $this->rootNode->children()
            ->arrayNode('tasks')
            ->addDefaultsIfNotSet()
            ->children()
        ;
        $children->integerNode('max_age')
            ->defaultValue(180)
            ->info('Delay before a Completed Task is Deleted from Database.')
        ;
        $children->integerNode('try_count')
            ->info('Number of failure of a Task before considering it finished.')
            ->defaultValue(5)
        ;
        $children->integerNode('try_delay')
            ->defaultValue(120)
            ->info('Delay before restarting a task that fails. In Seconds')
        ;
    }
}
