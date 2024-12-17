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
 * Access to Workers Tasking Parameters
 */
trait WorkersParametersGettersTrait
{
    /**
     * Get Worker Watchdog Delay
     */
    public function getWorkerWatchdogDelay(): int
    {
        return (int) $this->config['watchdog_delay'];
    }

    /**
     * Get Worker Delay to Refresh Status in Database
     */
    public function getWorkerRefreshDelay(): int
    {
        return (int) $this->config['refresh_delay'];
    }

    /**
     * Get Worker Max Execution Time
     */
    public function getWorkerMaxAge(): int
    {
        return (int) $this->config['workers']['max_age'];
    }

    /**
     * Get Worker Max Memory Usage
     */
    public function getWorkerMaxMemory(): int
    {
        return (int) $this->config['workers']['max_memory'];
    }

    /**
     * Get Worker Max Task to Execute on a Single Run
     */
    public function getWorkerMaxTasks(): int
    {
        return (int) $this->config['workers']['max_tasks'];
    }
}
