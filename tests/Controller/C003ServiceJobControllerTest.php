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

use PHPUnit\Framework\Assert;
use Splash\Tasking\Tests\Jobs\TestServiceJob;

/**
 * Test of Service Jobs
 */
class C003ServiceJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Service Job Execution
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testServiceJob() : void
    {
        $nbTasks = 2;
        $watchDog = 0;

        //====================================================================//
        // Create a New Job
        $job = (new TestServiceJob());
        //====================================================================//
        // Add Job To Queue
        for ($i = 0; $i < $nbTasks; $i++) {
            //====================================================================//
            // Create a New Job
            $job = (new TestServiceJob());
            //====================================================================//
            // Add Job to Queue
            $job->add();
        }

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            usleep((int) (500 * 1E3)); // 500Ms

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($job->getToken()) > 0) {
                $taskFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($job->getToken()));

            if (0 == $this->tasksRepository->getActiveTasksCount($job->getToken())) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }

            $watchDog++;
        } while (($watchDog < (2 * $nbTasks + 2)) && ($taskEnded < 4));

        //====================================================================//
        //Verify All Tasks Are Finished
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($job->getToken()));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->delete((string) $job->getToken());
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        Assert::assertTrue($taskFound);
    }
}
