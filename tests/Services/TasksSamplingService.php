<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Tests\Services;

/**
 * Tasks Sampling Service
 * Collection of Dummy Specific Testing Functions
 */
class TasksSamplingService
{
    /**
     * Test Task Delay
     *
     * @param array $inputs Array with job parameters
     *
     * @return boolean
     */
    public function delayTask(array $inputs): bool
    {
        //====================================================================//
        // Pause
        if (array_key_exists("Delay", $inputs)) {
            echo "Service Job => Wait for ".$inputs["Delay"]." Seconds </br>";
            sleep($inputs["Delay"]);
        }

        return true;
    }
}
