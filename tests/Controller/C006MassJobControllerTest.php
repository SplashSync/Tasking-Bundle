<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
use Splash\Tasking\Tests\Jobs\TestMassJob;

/**
 * Test of Mass Jobs
 */
class C006MassJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Mass Job Execution
     *
     * @throws Exception
     */
    public function testMassJob() : void
    {
        //====================================================================//
        // Start a Long Job
        $startedAt = new DateTime();
        Assert::assertInstanceOf(TestMassJob::class, $this->addTask());
        //====================================================================//
        // Wait for Job Finished
        Assert::assertTrue(
            $this->getTasksManager()->waitUntilTaskCompleted(Configuration::getTokenSelfReleaseDelay())
        );
        $finishedAt = new DateTime();
        //====================================================================//
        // Verify Job Duration
        $delay = $finishedAt->getTimestamp() - $startedAt->getTimestamp();
        Assert::assertGreaterThan(3, $delay);
        Assert::assertLessThan(8, $delay);
    }

    /**
     * Add a New Test Mass Task & Run
     *
     * @return TestMassJob
     */
    private function addTask(): TestMassJob
    {
        //====================================================================//
        // Create a New Test Job
        $job = new TestMassJob();
        //====================================================================//
        // Setup Task Parameters
        $job
            ->setToken(self::randomStr())
            // Setup for 30 Tasks of 100ms
            ->setup(30)
        ;
        //====================================================================//
        // Save Task
        $job->add();

        return $job;
    }
}
