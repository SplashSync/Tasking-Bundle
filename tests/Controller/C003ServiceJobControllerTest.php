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

use BadPixxel\Tasking\Tests\Bundle\Jobs\ServiceJob;
use PHPUnit\Framework\Assert;

/**
 * Test of Service Jobs
 */
class C003ServiceJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Service Job Execution
     */
    public function testServiceJob() : void
    {
        $nbTasks = 2;
        $watchDog = 0;
        $token = ServiceJob::class;

        //====================================================================//
        // Build Task Options
        $options = ServiceJob::toOptions();
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(ServiceJob::class, $options)
        );

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            usleep((int) (500 * 1E3)); // 500Ms

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($token) > 0) {
                $taskFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($token));

            if (0 == $this->tasksRepository->getActiveTasksCount($token)) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }

            $watchDog++;
        } while (($watchDog < (2 * $nbTasks + 2)) && ($taskEnded < 4));

        //====================================================================//
        //Verify All Tasks Are Finished
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($token));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->delete((string) $token);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        Assert::assertTrue($taskFound);
    }
}
