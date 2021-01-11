<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Services;

use ArrayObject;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;

class Configuration
{
    /**
     * Tasking Doctrine Entity Manager
     *
     * @var ObjectManager
     */
    private static $entityManager;

    /**
     * Tasking Service Configuration Array
     *
     * @var ArrayObject
     */
    private static $config;

    /**
     * Class Constructor
     *
     * @param array    $config
     * @param Registry $doctrine
     */
    public function __construct(array $config, Registry $doctrine)
    {
        //====================================================================//
        // Link to entity manager Service
        self::$entityManager = $doctrine->getManager($config["entity_manager"]);
        //====================================================================//
        // Store Original Configuration
        self::$config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get Entity Manger for Tasking
     *
     * @return ObjectManager
     */
    public static function getEntityManager(): ObjectManager
    {
        return self::$entityManager;
    }
}
