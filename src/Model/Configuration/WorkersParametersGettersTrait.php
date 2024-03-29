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

namespace Splash\Tasking\Model\Configuration;

/**
 * Access to Workers Tasking Parameters
 */
trait WorkersParametersGettersTrait
{
    /**
     * @return int
     */
    public static function getWorkerWatchdogDelay(): int
    {
        return (int) self::$config['watchdog_delay'];
    }

    /**
     * @return int
     */
    public static function getWorkerRefreshDelay(): int
    {
        return (int) self::$config['refresh_delay'];
    }

    /**
     * @return int
     */
    public static function getWorkerMaxAge(): int
    {
        return (int) self::$config['workers']['max_age'];
    }

    /**
     * @return int
     */
    public static function getWorkerMaxMemory(): int
    {
        return (int) self::$config['workers']['max_memory'];
    }

    /**
     * @return int
     */
    public static function getWorkerMaxTasks(): int
    {
        return (int) self::$config['workers']['max_tasks'];
    }
}
