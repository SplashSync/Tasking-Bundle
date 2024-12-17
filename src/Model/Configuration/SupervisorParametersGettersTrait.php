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

namespace BadPixxel\Tasking\Model\Configuration;

/**
 * Access to Supervisor Tasking Parameters
 */
trait SupervisorParametersGettersTrait
{
    /**
     * Get Supervisor Max Execution Time
     */
    public function getSupervisorMaxAge(): int
    {
        return (int) $this->config['supervisor']['max_age'];
    }

    /**
     * Get Supervisor Max Memory Usage
     */
    public function getSupervisorMaxMemory(): int
    {
        return (int) $this->config['supervisor']['max_memory'];
    }

    /**
     * Get Supervisor Max Number of Workers
     */
    public function getSupervisorMaxWorkers(): int
    {
        return (int) $this->config['supervisor']['max_workers'];
    }

    /**
     * Get Supervisor Workers Refresh Delay
     */
    public function getSupervisorRefreshDelay(): int
    {
        return (int) $this->config['supervisor']['refresh_delay'];
    }
}
