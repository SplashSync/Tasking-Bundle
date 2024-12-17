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

namespace BadPixxel\Tasking\Services;

use BadPixxel\Tasking\Events\CheckSupervisorEvent;
use BadPixxel\Tasking\Helper\Timer;
use DateTime;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Supervisor Worker Management Service
 */
class SupervisorsManager extends WorkersManager implements EventSubscriberInterface
{
    /**
     * Max Number of Workers
     *
     * @var int
     */
    private int $maxWorkers = 5;

    //====================================================================//
    //  Event Subscriber
    //====================================================================//

    /**
     * Register Subscribed Events Actions
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            CheckSupervisorEvent::class => "checkSupervisor",
        );
    }

    //==============================================================================
    //      Supervisor Operations
    //==============================================================================

    /**
     * Get Max Number of Workers for Supervisor (since now)
     *
     * @throws Exception
     *
     * @return int
     */
    public function getMaxWorkers() : int
    {
        //====================================================================//
        // Load Config Value
        $maxWorkers = $this->configuration->getSupervisorMaxWorkers();
        //====================================================================//
        // Safety Checks
        if ($maxWorkers <= 0) {
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
     *
     * @throws Exception
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
        Timer::msSleep($this->configuration->getSupervisorRefreshDelay());
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
     * @throws Exception
     *
     * @return DateTime
     */
    protected function getWorkerMaxDate(): DateTime
    {
        $this->logger->info(
            "Supervisor Manager: This Worker will die in ".$this->configuration->getSupervisorMaxAge()." Seconds"
        );

        return new DateTime("+".$this->configuration->getSupervisorMaxAge()."Seconds");
    }

    /**
     * Get Max Memory Usage for Worker (in Mb)
     *
     * @return int
     */
    protected function getWorkerMaxMemory(): int
    {
        return $this->configuration->getSupervisorMaxMemory();
    }
}
