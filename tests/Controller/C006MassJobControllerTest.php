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

use BadPixxel\Tasking\Tests\Bundle\Jobs\MassJob;
use DateTime;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * Test of Mass Jobs
 */
class C006MassJobControllerTest extends AbstractTestController
{
    /**
     * Test of A Mass Job Execution
     *
     * @dataProvider jobsRepeatableProvider
     *
     * @throws Exception
     */
    public function testMassJob(int $nbTasks, int $msDelay) : void
    {
        //====================================================================//
        // Start a Long Job
        $startedAt = new DateTime();
        $this->addTask($nbTasks, $msDelay);
        //====================================================================//
        // Wait for Job Finished
        Assert::assertTrue(
            $this->getTasksManager()->waitUntilTaskCompleted($this->getConfiguration()->getTokenSelfReleaseDelay())
        );
        $finishedAt = new DateTime();
        //====================================================================//
        // Verify Job Duration
        $delay = $finishedAt->getTimestamp() - $startedAt->getTimestamp();
        $estimated = $nbTasks * $msDelay / 1E3;
        Assert::assertGreaterThan(0.75 * $estimated, $delay);
        Assert::assertLessThan(1.25 * $estimated, $delay);
    }

    /**
     * Add a New Test Mass Task & Run
     */
    private function addTask(int $nbTasks, int $msDelay): void
    {
        //====================================================================//
        // Build Task Options
        $options = MassJob::toOptions(
            nbTasks: $nbTasks,
            msDelay: $msDelay,
            token: self::randomStr()
        );
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(MassJob::class, $options)
        );
    }
}
