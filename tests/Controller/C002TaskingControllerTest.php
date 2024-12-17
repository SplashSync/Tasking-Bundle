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

use BadPixxel\Tasking\Tests\Bundle\Jobs\JobWithRandomInputs;
use PHPUnit\Framework\Assert;

/**
 * Test of Tasking Controller
 */
class C002TaskingControllerTest extends AbstractTestController
{
    const TEST_DEPTH = 100;

    /**
     * Test of A Simple Long Task List Execution
     */
    public function testSimpleTask(): void
    {
        $nbTasks = 2;
        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            $this->addTask($this->randomStr);
        }

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            sleep(1);

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($this->randomStr) > 0) {
                $taskFound = true;
            }
            //====================================================================//
            // We Found Only One Task Running
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($this->randomStr));

            if (0 == $this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while ($taskEnded < 2);

        //====================================================================//
        //Verify All Tasks Are Finished
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($this->randomStr));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->delete($this->randomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        Assert::assertTrue($taskFound);
    }

    /**
     * Test of Multiple Micro Tasks Execution
     */
    public function testMicroTask(): void
    {
        $nbTasks = self::TEST_DEPTH;
        $delay = 100;     // 30ms

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            $this->addMicroTask($this->randomStr, $delay);
        }

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            usleep((int) (($delay / 10) * 1E3));

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($this->randomStr) > 0) {
                $taskFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($this->randomStr));

            if (0 == $this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while ($taskEnded < 1000);

        //====================================================================//
        //Verify All Tasks Are Finished
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($this->randomStr));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->delete($this->randomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        Assert::assertTrue($taskFound);
    }

    /**
     * Test of Multiple Micro Tasks Execution
     *
     * @SuppressWarnings(ElseExpression)
     */
    public function testMultiMicroTask(): void
    {
        $nbTasks = self::TEST_DEPTH;
        $delay = 30;        // 30ms
        $taskAFound = $taskBFound = $taskCFound = false;

        //====================================================================//
        // Create a New Set of Micro Tasks
        $tokenA = self::randomStr();
        $tokenB = self::randomStr();
        $tokenC = self::randomStr();

        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            $this->addMicroTask($tokenA, $delay);
            $this->addMicroTask($tokenB, $delay);
            $this->addMicroTask($tokenC, $delay);
        }

        $this->entityManager->clear();
        Assert::assertGreaterThan(0, $this->tasksRepository->getWaitingTasksCount());

        //====================================================================//
        // While Tasks Are Running
        $taskEnded = 0;
        do {
            usleep((int) (($delay / 10) * 1E3));

            $this->entityManager->clear();
            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($tokenA) > 0) {
                $taskAFound = true;
            }
            if ($this->tasksRepository->getActiveTasksCount($tokenB) > 0) {
                $taskBFound = true;
            }
            if ($this->tasksRepository->getActiveTasksCount($tokenC) > 0) {
                $taskCFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenA));
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenB));
            Assert::assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenC));

            if (0 == $this->tasksRepository->getActiveTasksCount()) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while ($taskEnded < 1000);

        //====================================================================//
        //Verify All Tasks Are Finished
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenA));
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenB));
        Assert::assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenC));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->delete($tokenA);
        $this->tokenRepository->delete($tokenB);
        $this->tokenRepository->delete($tokenC);

        //====================================================================//
        // Clean Finished Tasks
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Tasks Running
        Assert::assertTrue($taskAFound);
        Assert::assertTrue($taskBFound);
        Assert::assertTrue($taskCFound);
    }

    /**
     * Create a New Simple Job to Queue
     */
    private function addTask(string $token): void
    {
        //====================================================================//
        // Build Task Options
        $options = JobWithRandomInputs::toOptions(token: $token);
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(JobWithRandomInputs::class, $options)
        );
    }

    /**
     * Create a New Micro Job to Queue
     */
    private function addMicroTask(string $token, int $delay): void
    {
        //====================================================================//
        // Build Task Options
        $options = JobWithRandomInputs::toOptions(delay: 0, delayMs: $delay, token: $token);
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(JobWithRandomInputs::class, $options)
        );
    }
}
