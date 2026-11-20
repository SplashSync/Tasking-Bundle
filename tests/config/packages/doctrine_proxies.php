<?php

/*
 *  Copyright (C) BadPixxel <www.badpixxel.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use Composer\InstalledVersions;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

//====================================================================//
// Doctrine Proxies Configuration
//
// DoctrineBundle 3 (required by Symfony 8) removed proxy generation options:
// ORM 3 relies on native lazy objects. Only configure them on DoctrineBundle 2.
//====================================================================//
return static function (ContainerConfigurator $container): void {
    $version = (string) InstalledVersions::getVersion('doctrine/doctrine-bundle');
    if (version_compare($version, '3.0.0', '>=')) {
        return;
    }
    //====================================================================//
    // Production: Use Pre-Generated Proxies
    if ('prod' === $container->env()) {
        $container->extension('doctrine', array('orm' => array(
            'auto_generate_proxy_classes' => false,
            'proxy_dir' => '%kernel.build_dir%/doctrine/orm/Proxies',
        )));

        return;
    }
    //====================================================================//
    // Other Environments: Generate Proxies on the Fly in Debug Mode
    $container->extension('doctrine', array('orm' => array(
        'auto_generate_proxy_classes' => '%kernel.debug%',
    )));
};
