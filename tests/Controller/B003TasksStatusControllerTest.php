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

namespace BadPixxel\Tasking\Tests\Controller;

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * Test of Tasks Status Helper
 */
class B003TasksStatusControllerTest extends AbstractTestController
{
    /**
     * Test of Token Status Data Collector
     *
     * @throws Exception
     */
    public function testTokenStatusManager() : void
    {
        $statusMonitor = $this->getStatusMonitor();
        $task = new Task();
        $task->setJobToken(self::randomStr());
        //====================================================================//
        // Acquire a Random Token
        Assert::assertTrue($this->getTokenManager()->acquire($task));
        //====================================================================//
        // Check Token Status
        Assert::assertTrue($statusMonitor->hasToken());
        $lifetime = $statusMonitor->getTokenLifetime();
        Assert::assertGreaterThanOrEqual($this->getConfiguration()->getTokenSelfReleaseDelay(), $lifetime);
        //====================================================================//
        // Check Token Lifetime
        for ($i = 0; $i < 2; $i++) {
            sleep(1);
            Assert::assertLessThan($lifetime, $statusMonitor->getTokenLifetime());
            $lifetime = $statusMonitor->getTokenLifetime();
        }
        //====================================================================//
        // Release Token
        Assert::assertTrue($this->getTokenManager()->release());
        //====================================================================//
        // Check Token Status
        Assert::assertFalse($statusMonitor->hasToken());
        Assert::assertNull($statusMonitor->getTokenLifetime());
    }

    /**
     * Test of Watchdog Status Data Collector
     *
     * @throws Exception
     */
    public function testLifetimeStatusManager() : void
    {
        $config = $this->getConfiguration();
        $statusMonitor = $this->getStatusMonitor();
        //====================================================================//
        // Check Initial Status
        $initialStatus = $statusMonitor->getStatus();
        Assert::assertNull($initialStatus["job"]);
        Assert::assertArrayHasKey("token", $initialStatus);
        Assert::assertNull($initialStatus["token"]);
        Assert::assertArrayHasKey("watchdog", $initialStatus);
        Assert::assertNull($initialStatus["watchdog"]);
        Assert::assertArrayHasKey("remaining", $initialStatus);
        Assert::assertNull($initialStatus["remaining"]);
        Assert::assertArrayHasKey("expandable", $initialStatus);
        Assert::assertNull($initialStatus["expandable"]);

        //====================================================================//
        // Simulate Token Acquired
        $statusMonitor->setTokenAcquired($this->randomStr);
        Assert::assertGreaterThanOrEqual($config->getTokenSelfReleaseDelay(), $statusMonitor->getTokenLifetime());
        Assert::assertEquals($config->getTokenSelfReleaseDelay(), $statusMonitor->getStatus()["remaining"]);
        Assert::assertEquals($config->getTokenSelfReleaseDelay(), $statusMonitor->getStatus()["expandable"]);

        //====================================================================//
        // Simulate Job Started
        $statusMonitor->setJobStarted();
        Assert::assertGreaterThanOrEqual($config->getTasksErrorDelay(), $statusMonitor->getJobLifetime());
        Assert::assertEquals($config->getTasksErrorDelay(), $statusMonitor->getStatus()["remaining"]);
        Assert::assertEquals($config->getTasksErrorDelay(), $statusMonitor->getStatus()["expandable"]);

        //====================================================================//
        // Reset Watchdog
        $statusMonitor->resetWatchdog();
        Assert::assertGreaterThanOrEqual($config->getWorkerWatchdogDelay(), $statusMonitor->getWatchdogLifetime());
        Assert::assertEquals($config->getWorkerWatchdogDelay(), $statusMonitor->getStatus()["remaining"]);
        Assert::assertEquals($config->getTasksErrorDelay(), $statusMonitor->getStatus()["expandable"]);

        //====================================================================//
        // Simulate Job Finished
        $statusMonitor->setJobFinished();
        Assert::assertNull($statusMonitor->getJobLifetime());
        Assert::assertEquals($config->getWorkerWatchdogDelay(), $statusMonitor->getStatus()["remaining"]);
        Assert::assertEquals($config->getTokenSelfReleaseDelay(), $statusMonitor->getStatus()["expandable"]);
        //====================================================================//
        // Reset Time Limit to Infinite (resetWatchdog sets it to a finite value)
        set_time_limit(0);
    }

    /**
     * Test of Watchdog Status Data Collector
     *
     * @throws Exception
     */
    public function testLifetimeExpandRequests() : void
    {
        $config = $this->getConfiguration();
        $statusMonitor = $this->getStatusMonitor();
        //====================================================================//
        // Simulate Task Startup
        $statusMonitor->resetWatchdog();
        $statusMonitor->setTokenAcquired($this->randomStr);
        $statusMonitor->setJobStarted();
        //====================================================================//
        // Verify Status
        Assert::assertEquals($config->getWorkerWatchdogDelay(), $statusMonitor->getRemainingLifetime());
        Assert::assertEquals($config->getTasksErrorDelay(), $statusMonitor->getExpendableLifetime());
        //====================================================================//
        // Reset Time Limit to Infinite (resetWatchdog sets it to a finite value)
        set_time_limit(0);
    }

    /**
     * Get Task Factory
     */
    protected function getStatusMonitor(): StatusMonitor
    {
        static $service;

        if (!isset($service)) {
            Assert::assertInstanceOf(
                StatusMonitor::class,
                $service = $this->getContainer()->get(StatusMonitor::class)
            );
        }

        return $service;
    }
}
