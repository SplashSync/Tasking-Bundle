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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Splash\Tasking\Model\Configuration as ConfigurationTraits;

class Configuration
{
    use ConfigurationTraits\CoreParametersGettersTrait;
    use ConfigurationTraits\ServerParametersGettersTrait;
    use ConfigurationTraits\DoctrineGettersTrait;
    use ConfigurationTraits\SupervisorParametersGettersTrait;
    use ConfigurationTraits\WorkersParametersGettersTrait;
    use ConfigurationTraits\StaticParametersGettersTrait;
    use ConfigurationTraits\TokenParametersGettersTrait;
    use ConfigurationTraits\TasksParametersGettersTrait;

    /**
     * Tasking Service Configuration Array
     *
     * @var array
     */
    protected static $config;

    /**
     * Class Constructor
     *
     * @param array    $configuration
     * @param Registry $registry
     *
     * @throws Exception
     */
    public function __construct(array $configuration, Registry $registry)
    {
        //====================================================================//
        // Complete & Store Configuration
        self::loadConfiguration($configuration);
        //====================================================================//
        // Setup Doctrine Services
        self::setupEntityManager($registry);
    }

    /**
     * Check if Configuration is ready
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isReady(): bool
    {
        if (!isset(self::$config)) {
            throw new Exception("Tasking Bundle Configuration is NOT Loaded");
        }

        return true;
    }

    /**
     * Get Raw Configuration for Tasking
     *
     * @param array $configuration
     *
     * @throws Exception
     *
     * @return array
     */
    public static function loadConfiguration(array $configuration): array
    {
        //====================================================================//
        // Validate Configuration
        $validation = self::validateConfiguration($configuration);
        if (!is_null($validation)) {
            throw new Exception($validation);
        }
        //====================================================================//
        // Complete & Store Configuration
        self::$config = self::completeConfiguration($configuration);

        return self::$config;
    }

    /**
     * Validate Initial Configuration for Tasking
     *
     * @return null|string
     */
    private static function validateConfiguration(array $configuration): ?string
    {
        //====================================================================//
        // Validate Number of Workers
        if (empty($configuration['supervisor']['max_workers']) || (0 >= $configuration['supervisor']['max_workers'])) {
            return "Number of Workers must by above 0";
        }
        //====================================================================//
        // Validate Watchdog delay
        if ($configuration['watchdog_delay'] <= $configuration['refresh_delay']) {
            return "Watchdog delay MUST be greater than Refresh delay";
        }

        return null;
    }

    /**
     * Complete Initial Configuration for Tasking
     *
     * @return array
     */
    private static function completeConfiguration(array $configuration): array
    {
        //====================================================================//
        // Compute Tasks Parameters
        self::completeTasksConfiguration($configuration);
        //====================================================================//
        // Compute Token Parameters
        self::completeTokenConfiguration($configuration);

        return $configuration;
    }
}
