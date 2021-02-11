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
 * Access to Supervisor Tasking Parameters
 */
trait SupervisorParametersGettersTrait
{
    /**
     * @return int
     */
    public static function getSupervisorMaxAge(): int
    {
        return (int) self::$config['supervisor']['max_age'];
    }

    /**
     * @return int
     */
    public static function getSupervisorMaxMemory(): int
    {
        return (int) self::$config['supervisor']['max_memory'];
    }

    /**
     * @return int
     */
    public static function getSupervisorMaxWorkers(): int
    {
        return (int) self::$config['supervisor']['max_workers'];
    }

    /**
     * @return int
     */
    public static function getSupervisorRefreshDelay(): int
    {
        return (int) self::$config['supervisor']['refresh_delay'];
    }
}
