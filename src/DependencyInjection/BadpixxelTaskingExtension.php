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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\Assert\Assert;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BadpixxelTaskingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        Assert::string($env = $container->getParameter('kernel.environment'));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'), $env);
        $loader->load('services.yaml');

        $container->setParameter('badpixxel_tasking', $config);

        $bundles = $container->getParameter('kernel.bundles');
        //====================================================================//
        // Register Admin Services if Sonata Admin is Installed
        //====================================================================//
        if (is_array($bundles) && isset($bundles['SonataAdminBundle'])) {
            $loader->load('services/admin.yaml');
        }
        //====================================================================//
        // Register Paddock Services if Paddock is Installed
        //====================================================================//
        if (is_array($bundles) && isset($bundles['PaddockCoreBundle'])) {
            $loader->load('services/paddock.yaml');
        }
        //====================================================================//
        // Register Components if Symfony Live Component Bundle is Installed
        //====================================================================//
        if (is_array($bundles) && isset($bundles['LiveComponentBundle'])) {
            $loader->load('services/components.yaml');
        }
    }
}
