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

namespace Splash\Tasking\Tests\Controller;

use DateTime;
use Exception;
use PHPUnit\Framework\Assert;
use Splash\Tasking\Services\Configuration;
use Splash\Tasking\Tests\Jobs\LongJob;

/**
 * Test of Long Jobs
 */
class C004LongJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Long Service Job Execution
     * @throws Exception
     */
    public function testLongJobExecution() : void
    {
        //====================================================================//
        // Start a Long Job
        $startedAt = new DateTime();
        Assert::assertInstanceOf(LongJob::class, $this->addTask());
        //====================================================================//
        // Wait for Job Finished
        Assert::assertTrue(
            $this->getTasksManager()->waitUntilTaskCompleted(Configuration::getTokenSelfReleaseDelay())
        );
        $finishedAt = new DateTime();
        //====================================================================//
        // Verify Job Duration
        $delay = $finishedAt->getTimestamp() - $startedAt->getTimestamp();
        Assert::assertGreaterThan(0, $delay);
        Assert::assertGreaterThanOrEqual(Configuration::getWorkerWatchdogDelay(), $delay);
        Assert::assertLessThan(Configuration::getTokenSelfReleaseDelay(), $delay);
    }

    /**
     * Add a New Test Long Task & Run
     *
     * @return LongJob
     */
    private function addTask(): LongJob
    {
        //====================================================================//
        // Create a New Test Job
        $job = new LongJob();
        //====================================================================//
        // Setup Task Parameters
        $job
            ->setToken(self::randomStr());
        //====================================================================//
        // Save Task
        $job->add();

        return $job;
    }
}
