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

$bundles = array(
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => array('all' => true),
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => array('all' => true),
    Symfony\Bundle\TwigBundle\TwigBundle::class => array('all' => true),
    Symfony\Bundle\MonologBundle\MonologBundle::class => array('all' => true),
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => array('all' => true),

    Splash\Tasking\SplashTaskingBundle::class => array('all' => true),

    Symfony\Bundle\DebugBundle\DebugBundle::class => array('dev' => true, 'test' => true),
);

if (class_exists(BadPixxel\Paddock\Core\PaddockCoreBundle::class)) {
    $bundles[BadPixxel\Paddock\Core\PaddockCoreBundle::class] = array('all' => true);
}

return $bundles;
