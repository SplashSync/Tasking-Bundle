<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Tests\Controller;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestJob;

/**
 * Test of Tasking Controller
 */
class C002TaskingControllerTest extends AbstractTestController
{
    const TEST_DETPH = 100;

    /**
     * Test of A Simple Long Task List Execution
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testSimpleTask(): void
    {
        $nbTasks = 2;
        $watchDog = 0;

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            $this->AddTask($this->randomStr);
        }

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            sleep(1);

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2, $this->tasksRepository->getActiveTasksCount($this->randomStr));

            if (0 == $this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while (($watchDog < ($nbTasks + 2)) && ($taskEnded < 2));

        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0, $this->tasksRepository->getWaitingTasksCount($this->randomStr));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->Delete($this->randomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($taskFound);
    }

    /**
     * Test of Multiple Micro Tasks Execution
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testMicroTask(): void
    {
        $nbTasks = self::TEST_DETPH;
        $watchDog = 0;
        $delay = 100;     // 30ms

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            $this->assertInstanceOf(TestJob::class, $this->AddMicroTask($this->randomStr, $delay));
        }

        //====================================================================//
        // While Tasks Are Running
        $taskFound = false;
        $taskEnded = 0;
        do {
            usleep((int) (($delay / 10) * 1E3));

            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2, $this->tasksRepository->getActiveTasksCount($this->randomStr));

            if (0 == $this->tasksRepository->getActiveTasksCount($this->randomStr)) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while (($watchDog < ($nbTasks + 2)) && ($taskEnded < 1000));

        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0, $this->tasksRepository->getWaitingTasksCount($this->randomStr));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->Delete($this->randomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->tasksRepository->Clean(0);

        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($taskFound);
    }

    /**
     * Test of Multiple Micro Tasks Execution
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testMultiMicroTask(): void
    {
        $nbTasks = self::TEST_DETPH;
        $watchDog = 0;
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
            $this->assertInstanceOf(TestJob::class, $this->AddMicroTask($tokenA, $delay));
            $this->assertInstanceOf(TestJob::class, $this->AddMicroTask($tokenB, $delay));
            $this->assertInstanceOf(TestJob::class, $this->AddMicroTask($tokenC, $delay));
        }

        $this->entityManager->clear();
        $this->assertGreaterThan(0, $this->tasksRepository->getWaitingTasksCount());

        //====================================================================//
        // While Tasks Are Running
        $taskEnded = 0;
        do {
            usleep((int) (($delay / 10) * 1E3));

            $this->entityManager->Clear();
            //====================================================================//
            // We Found Our Task Running
            if ($this->tasksRepository->getActiveTasksCount($tokenA)) {
                $taskAFound = true;
            }
            if ($this->tasksRepository->getActiveTasksCount($tokenB)) {
                $taskBFound = true;
            }
            if ($this->tasksRepository->getActiveTasksCount($tokenC)) {
                $taskCFound = true;
            }

            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenA));
            $this->assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenB));
            $this->assertLessThan(2, $this->tasksRepository->getActiveTasksCount($tokenC));

            if (0 == $this->tasksRepository->getActiveTasksCount()) {
                $taskEnded++;
            } else {
                $taskEnded = 0;
            }
        } while (($watchDog < ($nbTasks + 2)) && ($taskEnded < 1000));

        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenA));
        $this->assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenB));
        $this->assertEquals(0, $this->tasksRepository->getWaitingTasksCount($tokenC));

        //====================================================================//
        // Delete Current Token
        $this->tokenRepository->Delete($tokenA);
        $this->tokenRepository->Delete($tokenB);
        $this->tokenRepository->Delete($tokenC);

        //====================================================================//
        // Clean Finished Tasks
        $this->tasksRepository->Clean(0);

        //====================================================================//
        // Check We Found Our Tasks Running
        $this->assertTrue($taskAFound);
        $this->assertTrue($taskBFound);
        $this->assertTrue($taskCFound);
    }

    /**
     * Create a New Simple Job to Queue
     *
     * @param string $token
     *
     * @return TestJob
     */
    private function addTask(string $token): TestJob
    {
        //====================================================================//
        // Create a New Job
        $job = (new TestJob())
            ->setToken($token)
            ->setInputs(array(
                "Delay-S" => 1,
                "random" => self::randomStr(),
            ))
        ;
        //====================================================================//
        // Add Job to Queue
        $this->dispatcher->dispatch("tasking.add", $job);

        return $job;
    }

    /**
     * Create a New Micro Job to Queue
     *
     * @param string $token
     * @param int    $delay
     *
     * @return TestJob
     */
    private function addMicroTask(string $token, int $delay): TestJob
    {
        //====================================================================//
        // Create a New Job
        $job = (new TestJob())
            ->setToken($token)
            ->setInputs(array(
                "Delay-Ms" => $delay,
                "random" => self::randomStr(),
            ))
        ;
        //====================================================================//
        // Add Job to Queue
        $this->dispatcher->dispatch("tasking.add", $job);

        return $job;
    }
}
