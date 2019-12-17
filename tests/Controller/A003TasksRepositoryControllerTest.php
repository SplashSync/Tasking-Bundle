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

use PHPUnit\Framework\Assert;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestJob;

/**
 * Test Tasks Repository
 */
class A003TasksRepositoryControllerTest extends AbstractTestController
{
    /**
     * @var int
     */
    private $maxItems = 10;

    /**
     * @var string
     */
    private $randomStrA;

    /**
     * @var string
     */
    private $randomStrB;

    /**
     * @var string
     */
    private $randomStrC;

    /**
     * Test Delete All Tasks
     */
    public function testDeleteAllTaskss(): void
    {
        //====================================================================//
        // Delete All Tasks Completed
        $this->tasksRepository->clean(0);
        //====================================================================//
        // Verify Delete All Tokens
        Assert::assertEquals(0, $this->tasksRepository->clean(0));
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
    }

    /**
     * Test Counting of Waiting Tasks
     */
    public function testWaitingTasksCount(): void
    {
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStrA = self::randomStr();
        $this->randomStrB = self::randomStr();
        $this->randomStrC = self::randomStr();

        //====================================================================//
        // Create a Task with Token
        $this->insertTask($this->randomStrA);
        $this->insertTask($this->randomStrB);
        $this->insertTask($this->randomStrC);

        //====================================================================//
        // Verify Waiting Tasks
        Assert::assertEquals(
            1,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrA)
        );
        Assert::assertEquals(
            1,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrB)
        );
        Assert::assertEquals(
            1,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrC)
        );
        Assert::assertGreaterThan(
            2,
            $this->tasksRepository->getWaitingTasksCount()
        );
    }

    /**
     * Test Counting of Actives Tasks
     */
    public function testActiveTasksCount(): void
    {
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStrA = self::randomStr();
        $this->randomStrB = self::randomStr();
        $this->randomStrC = self::randomStr();

        //====================================================================//
        // Create a Task with Token
        $this->insertTask($this->randomStrA);
        $this->insertTask($this->randomStrB);
        $this->insertTask($this->randomStrC);

        //====================================================================//
        // Load a Task
        $task = $this->tasksRepository->findOneBy(
            array("jobToken" => $this->randomStrA)
        );

        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertFalse($task->isRunning());
        Assert::assertFalse($task->isFinished());

        //====================================================================//
        // Init Active Count Tasks
        $offset = $this->tasksRepository->getActiveTasksCount();
        //====================================================================//
        // Verify Active Count Tasks
        Assert::assertEquals(
            0,
            $this->tasksRepository->getActiveTasksCount($this->randomStrA)
        );

        //====================================================================//
        // Set Task As Running
        $this->startTask($task);

        //====================================================================//
        // Verify Active Count Tasks
        Assert::assertEquals(
            1,
            $this->tasksRepository->getActiveTasksCount($this->randomStrA)
        );
        Assert::assertGreaterThan(
            $offset,
            $this->tasksRepository->getActiveTasksCount()
        );
    }

    /**
     * Test Counting of Pending Tasks (Waiting or Pending)
     */
    public function testPendingTasksCount(): void
    {
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStrA = self::randomStr();
        $this->randomStrB = self::randomStr();
        $this->randomStrC = self::randomStr();

        //====================================================================//
        // Create X Tasks with Token
        for ($i = 0; $i < $this->maxItems; $i++) {
            $this->insertTask($this->randomStrA);
            $this->insertTask($this->randomStrB);
            $this->insertTask($this->randomStrC);
        }

        //====================================================================//
        // Load a Task
        $task = $this->tasksRepository->findOneBy(
            array("jobToken" => $this->randomStrA)
        );
        //====================================================================//
        // Set Task As Running
        $this->startTask($task);

        //====================================================================//
        // Verify Waiting Tasks
        Assert::assertEquals(
            $this->maxItems - 1,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrA)
        );
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrB)
        );
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getWaitingTasksCount($this->randomStrC)
        );
        //====================================================================//
        // Verify Active Tasks
        Assert::assertEquals(
            1,
            $this->tasksRepository->getActiveTasksCount($this->randomStrA)
        );
        Assert::assertEquals(
            0,
            $this->tasksRepository->getActiveTasksCount($this->randomStrB)
        );
        Assert::assertEquals(
            0,
            $this->tasksRepository->getActiveTasksCount($this->randomStrC)
        );

        //====================================================================//
        // Verify Pending Tasks
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getPendingTasksCount($this->randomStrA)
        );
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getPendingTasksCount($this->randomStrB)
        );
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getPendingTasksCount($this->randomStrC)
        );
    }

    /**
     * Test Counting of User Pending Tasks (Waiting or Pending)
     */
    public function testUserPendingTasksCount(): void
    {
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Generate a Random Index Key
        $key = self::randomStr();
        //====================================================================//
        // Create X Tasks with Token
        for ($i = 0; $i < $this->maxItems; $i++) {
            $this->insertTask(null, $key);
        }

        //====================================================================//
        // Verify Waiting Tasks
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getWaitingTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Active Tasks
        Assert::assertEquals(
            0,
            $this->tasksRepository->getActiveTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Pending Tasks
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getPendingTasksCount(null, null, $key)
        );

        //====================================================================//
        // Load Tasks List
        $tasks = $this->tasksRepository->findBy(
            array("jobIndexKey1" => $key)
        );
        Assert::assertEquals($this->maxItems, count($tasks));
        //====================================================================//
        // Set Task As Running
        $activeTasks = (int) ($this->maxItems / 2);
        for ($i = 0; $i < $activeTasks; $i++) {
            $this->startTask($tasks[$i]);
        }

        //====================================================================//
        // Verify Waiting Tasks
        Assert::assertEquals(
            $this->maxItems - $activeTasks,
            $this->tasksRepository->getWaitingTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Active Tasks
        Assert::assertEquals(
            $activeTasks,
            $this->tasksRepository->getActiveTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Pending Tasks
        Assert::assertEquals(
            $this->maxItems,
            $this->tasksRepository->getPendingTasksCount(null, null, $key)
        );

        //====================================================================//
        // Load Tasks List
        $task = $this->tasksRepository->findOneBy(
            array("jobIndexKey1" => $key)
        );
        Assert::assertEquals($this->maxItems, count($tasks));
        //====================================================================//
        // Set Task As Finished
        $this->finishTask($task);
        $activeTasks--;

        //====================================================================//
        // Verify Waiting Tasks
        Assert::assertEquals(
            $this->maxItems - ($activeTasks + 1),
            $this->tasksRepository->getWaitingTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Active Tasks
        Assert::assertEquals(
            $activeTasks,
            $this->tasksRepository->getActiveTasksCount(null, null, $key)
        );
        //====================================================================//
        // Verify Pending Tasks
        Assert::assertEquals(
            $this->maxItems - 1,
            $this->tasksRepository->getPendingTasksCount(null, null, $key)
        );
    }

    /**
     * Test Get Next Task Function
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetNextTask(): void
    {
        //====================================================================//
        // Load Tasks Parameters
        $options = $this->getContainer()->getParameter("splash_tasking")["tasks"];
        $options["try_delay"] = $options["error_delay"] = 10;
        $noErrorsOptions = $options;
        $noErrorsOptions["error_delay"] = -1;
        $noRetryOptions = $options;
        $noRetryOptions["try_delay"] = -1;

        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));

        //====================================================================//
        // Generate a Random Token Name
        $this->randomStr = self::randomStr();

        //====================================================================//
        // Create a Task with Token
        $this->addTask($this->randomStr);
        //====================================================================//
        // Verify
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($options, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($options, $this->randomStr, false));
        $task = $this->tasksRepository->getNextTask($options, $this->randomStr, false);

        //====================================================================//
        // Create Task Token
        Assert::assertTrue($this->tokenRepository->validate($this->randomStr));
        //====================================================================//
        // Acquire Token
        $token = $this->tokenRepository->acquire($this->randomStr);
        Assert::assertNotEmpty($token);
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($options, $this->randomStr, false));

        //====================================================================//
        // Set Task as Started
        $this->startTask($task);
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));

        //====================================================================//
        // Set Task as Completed
        $this->finishTask($task, 5);
        $task->setFinished(true);
        $this->entityManager->flush();
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));

        //====================================================================//
        // Set Task as Tried but Not Finished
        $this->startTask($task);
        $this->finishTask($task, 5);
        $task->setFinished(false);
        $this->entityManager->flush();
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));

        //====================================================================//
        // Release Token
        Assert::assertTrue($this->tokenRepository->release($this->randomStr));

        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));

        //====================================================================//
        // Set Task as Running but In Timeout
        $this->startTask($task);
        $task->setFinished(false);
        $this->entityManager->flush();
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertInstanceOf(Task::class, $this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));

        //====================================================================//
        // Set Task as Completed
        $this->finishTask($task, 0);
        $this->entityManager->flush();
        //====================================================================//
        // Verify
        Assert::assertNull($this->tasksRepository->getNextTask($options, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($options, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noRetryOptions, $this->randomStr, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, null, false));
        Assert::assertNull($this->tasksRepository->getNextTask($noErrorsOptions, $this->randomStr, false));
    }

    /**
     * Add a New Test Simple Task & Run
     *
     * @param string $token
     *
     * @return TestJob
     */
    public function addTask(string $token): TestJob
    {
        //====================================================================//
        // Create a New Test Job
        $job = new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $job
            ->setInputs(array("Delay-S" => 1))
            ->setToken($token);
        //====================================================================//
        // Save Task
        $this->dispatcher->dispatch("tasking.add", $job);

        return $job;
    }

    /**
     * Insert a New Test Simple Task (Do Not Start Workers)
     *
     * @param string $token
     * @param string $index1
     * @param string $index2
     *
     * @return TestJob
     */
    public function insertTask(string $token = null, string $index1 = null, string $index2 = null): TestJob
    {
        //====================================================================//
        // Generate Token if Needed
        if (is_null($token)) {
            $token = self::randomStr();
        }

        //====================================================================//
        // Create a New Test Job
        $job = new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $job
            ->setInputs(array("Delay-S" => 2, "random" => self::randomStr()))
            ->setToken($token);
        //====================================================================//
        // Setup Indexes
        if (!is_null($index1)) {
            $job->__set('indexKey1', $index1);
        }
        if (!is_null($index2)) {
            $job->__set('indexKey2', $index2);
        }
        //====================================================================//
        // Save Task
        $this->dispatcher->dispatch("tasking.insert", $job);

        return $job;
    }

    /**
     * Delete All Tasks In Db
     */
    public function deleteAllTasks(): void
    {
        $tasks = $this->tasksRepository->findAll();
        foreach ($tasks as $task) {
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }

        Assert::assertEmpty($this->tasksRepository->findAll());
    }

    /**
     * Delete All Tokens In Db
     */
    public function deleteAllTokens(): void
    {
        $tokens = $this->tokenRepository->findAll();
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
            $this->entityManager->flush();
        }

        Assert::assertEmpty($this->tokenRepository->findAll());
    }

    /**
     * Manually Start a Task
     *
     * @param Task $task
     *
     * @return Task
     */
    private function startTask(Task $task): Task
    {
        //====================================================================//
        // Manualy Start Task
        $this->invokeMethod($this->runner, "validateJob", array(&$task));
        $this->invokeMethod($this->runner, "prepareJob", array(&$task));
        //====================================================================//
        // Verify Task State
        Assert::assertFalse($task->isFinished());
        Assert::assertTrue($task->isRunning());
        Assert::assertNotEmpty($task->getStartedAt());
        Assert::assertNotEmpty($task->getStartedBy());
        //====================================================================//
        // Save
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Manually Finish a Task
     *
     * @param Task $task
     * @param int  $maxTry
     *
     * @return Task
     */
    private function finishTask(Task $task, int $maxTry = 0): Task
    {
        //====================================================================//
        // Manually Finish Task
        $this->invokeMethod($this->runner, "closeJob", array(&$task, $maxTry));
        //====================================================================//
        // Verify Task State
        if (0 == $maxTry) {
            Assert::assertTrue($task->isFinished());
        }
        Assert::assertFalse($task->isRunning());
        Assert::assertNotEmpty($task->getFinishedAt());
        //====================================================================//
        // Save
        $this->entityManager->flush();

        return $task;
    }
}
