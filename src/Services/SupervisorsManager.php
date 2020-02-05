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

use DateTime;
use Exception;
use Splash\Tasking\Tools\Timer;

/**
 * Supervisor Worker Management Service
 */
class SupervisorsManager extends WorkersManager
{
    /**
     * Max Number of Workers
     *
     * @var int
     */
    private $maxWorkers = 5;

    //==============================================================================
    //      Supervisor Operations
    //==============================================================================

    /**
     * Get Max Number of Workers for Supervisor (since now)
     *
     * @return int
     */
    public function getMaxWorkers() : int
    {
        //====================================================================//
        // Load Config Value
        $maxWorkers = $this->config->supervisor['max_workers'];
        //====================================================================//
        // Safety Checks
        if (!is_int($maxWorkers) || ($maxWorkers <= 0)) {
            throw new Exception("Invalid Number of Configured Workers");
        }
        //====================================================================//
        // Store Value
        $this->maxWorkers = $maxWorkers;
        //====================================================================//
        // Debug Output
        $this->logger->info("Supervisor Manager: This Supervisor will manage ".$this->maxWorkers." Workers");

        return $this->maxWorkers;
    }

    /**
     * Do Pause for Supervisor between two Refresh loop
     */
    public function doSupervision(): void
    {
        //====================================================================//
        // Refresh Status of Each Worker
        for ($processId = 1; $processId <= $this->maxWorkers; $processId++) {
            //====================================================================//
            // Check Status of this Worker in THIS Machine Name
            //====================================================================//
            if ($this->isRunning($processId)) {
                continue;
            }

            //====================================================================//
            // Start This Worker if Not Running
            //====================================================================//
            $this->start($processId);
        }
    }

    /**
     * Do Pause for Supervisor between two Refresh loop
     */
    public function doPause(): void
    {
        //====================================================================//
        // Wait
        Timer::msSleep((int) $this->config->supervisor["refresh_delay"]);
    }

    //==============================================================================
    //      Worker Config Informations
    //==============================================================================

    /**
     * Get Worker Command Type Name
     *
     * @return string
     */
    protected function getWorkerCommandName(): string
    {
        return ProcessManager::SUPERVISOR;
    }

    /**
     * Get Max Age for Worker (since now)
     *
     * @return DateTime
     */
    protected function getWorkerMaxDate(): DateTime
    {
        $this->logger->info("Supervisor Manager: This Worker will die in ".$this->config->supervisor['max_age']." Seconds");

        return new DateTime("+".$this->config->supervisor['max_age']."Seconds");
    }

    /**
     * Get Max Memory Usage for Worker (in Mb)
     *
     * @return int
     */
    protected function getWorkerMaxMemory(): int
    {
        return (int) $this->config->supervisor["max_memory"];
    }
}
