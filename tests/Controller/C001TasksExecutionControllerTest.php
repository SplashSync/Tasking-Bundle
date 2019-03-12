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
 * Test of Tasks Execution
 */
class C001TasksExecutionControllerTest extends AbstractTestController
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        //====================================================================//
        // CleanUp Tasks
        $this->DeleteAllTasks()->DeleteAllTokens();
    }

    /**
     * Test of a Basic Job Execution
     */
    public function testBasic(): void
    {
        //====================================================================//
        // Create a Simple Test Job
        $job = new TestJob();
        $job
            ->setInputs(array("Delay-Ms" => 100))
            ->setToken($this->randomStr);

        //====================================================================//
        // Add Job to Queue
        $this->dispatcher->dispatch("tasking.add", $job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneByJobToken($this->randomStr);

        //====================================================================//
        // Verify Task
        $this->assertInstanceOf(Task::class, $task);
        $this->assertFalse($task->isRunning());
        $this->assertTrue($task->isFinished());
        $this->assertNotEmpty($task->getOutputs());
        $this->assertNotEmpty($task->getStartedAt());
        $this->assertNotEmpty($task->getFinishedAt());
        $this->assertEquals(1, $task->getTry());
    }

    /**
     * Test of Task Errors Management
     *
     * @dataProvider jobsMethodsProvider
     *
     * @param string $method
     * @param bool   $finished
     */
    public function testTaskErrors(string $method, bool $finished): void
    {
        //====================================================================//
        // Create a Simple Test Job
        $job = new TestJob();
        $job
            ->setInputs(array(
                "Delay-Ms" => 50,
                "Error-On-".$method => true,
            ))
            ->setToken($this->randomStr);

        //====================================================================//
        // Add Job to Queue
        $this->dispatcher->dispatch("tasking.add", $job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneByJobToken($this->randomStr);

        //====================================================================//
        // Verify Task
        $this->assertInstanceOf(Task::class, $task);
        $this->assertFalse($task->isRunning());
        $this->assertEquals($finished, $task->isFinished());
        $this->assertNotEmpty($task->getOutputs());
        $this->assertNotEmpty($task->getStartedAt());
        $this->assertNotEmpty($task->getFinishedAt());
        $this->assertNotEmpty($task->getFaultStr());
        $this->assertEquals(1, $task->getTry());
    }

    /**
     * Test of Task Exceptions Management
     *
     * @dataProvider jobsMethodsProvider
     *
     * @param string $method
     * @param bool   $finished
     */
    public function testTaskExceptions(string $method, bool $finished): void
    {
        //====================================================================//
        // Create a Simple Test Job
        $job = new TestJob();
        $job
            ->setInputs(array(
                "Delay-Ms" => 50,
                "Exception-On-".$method => true,
            ))
            ->setToken($this->randomStr);

        //====================================================================//
        // Add Job to Queue
        $this->dispatcher->dispatch("tasking.add", $job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneByJobToken($this->randomStr);

        //====================================================================//
        // Verify Task
        $this->assertInstanceOf(Task::class, $task);
        $this->assertFalse($task->isRunning());
        $this->assertFalse($task->isFinished());
        $this->assertNotEmpty($task->getOutputs());
        $this->assertNotEmpty($task->getStartedAt());
        $this->assertNotEmpty($task->getFinishedAt());
        $this->assertNotEmpty($task->getFaultStr());
        $this->assertEquals(1, $task->getTry());
    }

    /**
     * Test Wait Until Tasks Buffer is Empty
     */
    public function testWaitUntilTasksCompleted(): void
    {
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();

        //====================================================================//
        // Test with no Tasks in Buffer
        $this->assertTrue($this->tasks->waitUntilTaskCompleted());
        $this->assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 1 second Tasks in Buffer
        $this->AddTask($this->randomStr, 1);
        $this->assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        $this->assertTrue($this->tasks->waitUntilTaskCompleted());
        $this->assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 3 second Tasks in Buffer
        $this->AddTask($this->randomStr, 3);
        $this->assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        $this->assertFalse($this->tasks->waitUntilTaskCompleted(1));
        $this->assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        $this->assertTrue($this->tasks->waitUntilTaskCompleted());
        $this->assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 5 x 1 second Tasks in Buffer
        for ($i = 0; $i < 5; $i++) {
            $this->AddTask($this->randomStr, 1);
        }
        $this->assertEquals(5, $this->tasksRepository->getPendingTasksCount());
        $this->assertTrue($this->tasks->waitUntilTaskCompleted(2));
        $this->assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 12 x 1 second Tasks in Buffer
        for ($i = 0; $i < 12; $i++) {
            $this->AddTask($this->randomStr, 1);
        }
        $this->assertEquals(12, $this->tasksRepository->getPendingTasksCount());
        $this->assertFalse($this->tasks->waitUntilTaskCompleted(1));
    }

    /**
     * Return List of Jobs Methods to Test for Exception & Errors
     *
     * @return array
     */
    public function jobsMethodsProvider() : array
    {
        return array(
            array("Validate"    , false),
            array("Prepare"     , false),
            array("Execute"     , false),
            array("Finalize"    , true),
            array("Close"       , true),
        );
    }

    /**
     * Add a New Test Simple Task & Run
     *
     * @param string $token
     * @param int    $delay
     *
     * @return TestJob
     */
    private function addTask(string $token, int $delay = 1): TestJob
    {
        //====================================================================//
        // Create a New Test Job
        $job = new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $job
            ->setInputs(array("Delay-S" => $delay, "random" => self::randomStr()))
            ->setToken($token);
        //====================================================================//
        // Save Task
        $this->dispatcher->dispatch("tasking.add", $job);

        return $job;
    }

    /**
     * Delete All Tasks In Db
     *
     * @return $this
     */
    private function deleteAllTasks(): self
    {
        $tasks = $this->tasksRepository->findAll();
        foreach ($tasks as $task) {
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }

        $this->assertEmpty($this->tasksRepository->findAll());

        return $this;
    }

    /**
     * Delete All Tokens In Db
     *
     * @return $this
     */
    private function deleteAllTokens(): self
    {
        $tokens = $this->tokenRepository->findAll();
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
            $this->entityManager->flush();
        }

        $this->assertEmpty($this->tokenRepository->findAll());

        return $this;
    }

    /**
     * Wait Until Tasks Queue Completed
     *
     * @param int $limit
     */
    private function waitUntilCompleted(int $limit): void
    {
        //====================================================================//
        // Wait Unit get this Task Executed
        $watchDog = 0;
        $queue = 0;
        do {
            usleep((int) (500 * 1E3));  // 500Ms
            $watchDog++;

            $this->entityManager->clear();
            $queue = $this->tasksRepository->getWaitingTasksCount();
            $queue += $this->tasksRepository->getActiveTasksCount();
        } while (($watchDog < (2 * $limit)) && ($queue));
    }
}
