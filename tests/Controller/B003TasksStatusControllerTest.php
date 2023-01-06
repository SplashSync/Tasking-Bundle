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

namespace Splash\Tasking\Tests\Controller;

use Exception;
use PHPUnit\Framework\Assert;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Services\Configuration;
use Splash\Tasking\Tools\Status;

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
        //====================================================================//
        // Acquire a Random Token
        $token = $this->tokenRepository->acquire(self::randomStr());
        Assert::assertInstanceOf(Token::class, $token);
        //====================================================================//
        // Check Token Status
        Assert::assertTrue(Status::hasToken());
        $lifetime = Status::getTokenLifetime();
        Assert::assertGreaterThanOrEqual(Configuration::getTokenSelfReleaseDelay(), $lifetime);
        //====================================================================//
        // Check Token Lifetime
        for ($i = 0; $i < 2; $i++) {
            sleep(1);
            Assert::assertLessThan($lifetime, Status::getTokenLifetime());
            $lifetime = Status::getTokenLifetime();
        }
        //====================================================================//
        // Release Token
        Assert::assertTrue($this->tokenRepository->release($token->getName()));
        //====================================================================//
        // Check Token Status
        Assert::assertFalse(Status::hasToken());
        Assert::assertNull(Status::getTokenLifetime());
    }

    /**
     * Test of Watchdog Status Data Collector
     *
     * @throws Exception
     */
    public function testLifetimeStatusManager() : void
    {
        //====================================================================//
        // Check Initial Status
        $initialStatus = Status::getStatus();
        Assert::assertArrayHasKey("job", $initialStatus);
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
        Status::setTokenAcquired($this->randomStr);
        Assert::assertGreaterThanOrEqual(Configuration::getTokenSelfReleaseDelay(), Status::getTokenLifetime());
        Assert::assertEquals(Configuration::getTokenSelfReleaseDelay(), Status::getStatus()["remaining"]);
        Assert::assertEquals(Configuration::getTokenSelfReleaseDelay(), Status::getStatus()["expandable"]);

        //====================================================================//
        // Simulate Job Started
        Status::setJobStarted();
        Assert::assertGreaterThanOrEqual(Configuration::getTasksErrorDelay(), Status::getJobLifetime());
        Assert::assertEquals(Configuration::getTasksErrorDelay(), Status::getStatus()["remaining"]);
        Assert::assertEquals(Configuration::getTasksErrorDelay(), Status::getStatus()["expandable"]);

        //====================================================================//
        // Reset Watchdog
        Status::resetWatchdog();
        Assert::assertGreaterThanOrEqual(Configuration::getWorkerWatchdogDelay(), Status::getWatchdogLifetime());
        Assert::assertEquals(Configuration::getWorkerWatchdogDelay(), Status::getStatus()["remaining"]);
        Assert::assertEquals(Configuration::getTasksErrorDelay(), Status::getStatus()["expandable"]);

        //====================================================================//
        // Simulate Job Finished
        Status::setJobFinished();
        Assert::assertNull(Status::getJobLifetime());
        Assert::assertEquals(Configuration::getWorkerWatchdogDelay(), Status::getStatus()["remaining"]);
        Assert::assertEquals(Configuration::getTokenSelfReleaseDelay(), Status::getStatus()["expandable"]);
    }

    /**
     * Test of Watchdog Status Data Collector
     *
     * @throws Exception
     */
    public function testLifetimeExpandRequests() : void
    {
        //====================================================================//
        // Simulate Task Startup
        Status::resetWatchdog();
        Status::setTokenAcquired($this->randomStr);
        Status::setJobStarted();
        //====================================================================//
        // Verify Status
        Assert::assertEquals(Configuration::getWorkerWatchdogDelay(), Status::getRemainingLifetime());
        Assert::assertEquals(Configuration::getTasksErrorDelay(), Status::getExpendableLifetime());
    }
}
