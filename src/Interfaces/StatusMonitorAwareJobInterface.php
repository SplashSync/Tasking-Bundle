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

namespace BadPixxel\Tasking\Interfaces;

use BadPixxel\Tasking\Services\Tasks\StatusMonitor;

/**
 * This Job is Aware of Task Status Monitoring
 */
interface StatusMonitorAwareJobInterface
{
    /**
     * Get Task Status Monitor
     */
    public function getStatusMonitor(): StatusMonitor;

    /**
     * Ensure at least $nbSeconds remain for running current Job.
     *
     * If possible, watchdog (PHP Time Limit) will be extended.
     *
     * @return bool True if this delay is Allowed
     */
    public function requireLifetime(int $nbSeconds): bool;

    /**
     * Check if at least $nbSeconds remain for running current Job.
     *
     * @param int $nbSeconds
     *
     * @return bool True if this delay is Allowed
     */
    public function hasLifetime(int $nbSeconds): bool;
}
