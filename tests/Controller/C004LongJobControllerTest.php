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

use BadPixxel\Tasking\Tests\Bundle\Jobs\LongJob;
use DateTime;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * Test of Long Jobs
 */
class C004LongJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Long Service Job Execution
     *
     * @throws Exception
     */
    public function testLongJobNoRenewal() : void
    {
        $startedAt = new DateTime();
        //====================================================================//
        // Start a Long Job
        $this->addTask(false);
        //====================================================================//
        // Wait for Job Finished
        Assert::assertTrue(
            $this->getTasksManager()->waitUntilTaskCompleted($this->getConfiguration()->getTokenSelfReleaseDelay())
        );
        $finishedAt = new DateTime();
        //====================================================================//
        // Verify Job Duration
        $delay = $finishedAt->getTimestamp() - $startedAt->getTimestamp();
        Assert::assertGreaterThan(10, $delay);
        Assert::assertLessThan($this->getConfiguration()->getWorkerWatchdogDelay(), $delay);
    }

    /**
     * Test of A Long Service Job Execution
     *
     * @throws Exception
     */
    public function testLongJobWith() : void
    {
        $startedAt = new DateTime();
        //====================================================================//
        // Start a Long Job
        $this->addTask(true);
        //====================================================================//
        // Wait for Job Finished
        Assert::assertTrue(
            $this->getTasksManager()->waitUntilTaskCompleted($this->getConfiguration()->getTokenSelfReleaseDelay())
        );
        $finishedAt = new DateTime();
        //====================================================================//
        // Verify Job Duration
        $delay = $finishedAt->getTimestamp() - $startedAt->getTimestamp();
        Assert::assertGreaterThan(0, $delay);
        Assert::assertGreaterThanOrEqual($this->getConfiguration()->getWorkerWatchdogDelay(), $delay);
        Assert::assertLessThan($this->getConfiguration()->getTokenSelfReleaseDelay(), $delay);
    }

    /**
     * Add a New Test Long Task & Run
     *
     * @param bool $renewal
     */
    private function addTask(bool $renewal): void
    {
        //====================================================================//
        // Build Task Options
        $options = LongJob::toOptions(renewal: $renewal, token: self::randomStr());
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(LongJob::class, $options)
        );
    }
}
