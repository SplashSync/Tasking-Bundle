<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Model\Configuration;

/**
 * Access to Tasks Tasking Parameters
 */
trait TasksParametersGettersTrait
{
    /**
     * Get Tasks Configuration
     *
     * @return array
     */
    public static function getTasksConfiguration(): array
    {
        return self::$config['tasks'];
    }

    /**
     * @return int
     */
    public static function getTasksDeleteDelay(): int
    {
        return (int) self::$config['tasks']['max_age'];
    }

    /**
     * @return int
     */
    public static function getTasksMaxRetry(): int
    {
        return (int) self::$config['tasks']['try_count'];
    }

    /**
     * @return int
     */
    public static function getTasksRetryDelay(): int
    {
        return (int) self::$config['tasks']['try_delay'];
    }

    /**
     * @return int
     */
    public static function getTasksErrorDelay(): int
    {
        return (int) self::$config['tasks']['error_delay'];
    }

    /**
     * Complete Configuration for Tasks
     *
     * @param array $configuration
     *
     * @return array
     */
    private static function completeTasksConfiguration(array &$configuration): array
    {
        // Compute Tasks Error Delay
        $configuration["tasks"]["error_delay"] = 8 * $configuration["watchdog_delay"];

        return $configuration;
    }
}
