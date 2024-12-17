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

use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Tests\Bundle\Jobs\JobWithErrors;
use BadPixxel\Tasking\Tests\Bundle\Jobs\JobWithExceptions;
use BadPixxel\Tasking\Tests\Bundle\Jobs\JobWithRandomInputs;
use BadPixxel\Tasking\Tests\Bundle\Jobs\SimpleJob;
use Exception;
use PHPUnit\Framework\Assert;

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
        $this->deleteAllTasks()->deleteAllTokens();
    }

    /**
     * Test of a Basic Job Execution
     */
    public function testBasic(): void
    {
        //====================================================================//
        // Build Task Options
        $options = SimpleJob::toOptions(delay: 0, delayMs: 100, token: $this->randomStr);
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(SimpleJob::class, $options)
        );
        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->waitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneBy(
            array("jobToken" => $this->randomStr)
        );

        //====================================================================//
        // Verify Task
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertFalse($task->isRunning());
        Assert::assertTrue($task->isFinished());
        Assert::assertNotEmpty($task->getOutputs());
        Assert::assertNotEmpty($task->getStartedAt());
        Assert::assertNotEmpty($task->getFinishedAt());
        Assert::assertEquals(1, $task->getTry());
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
        // Build Task Options
        $options = array(
            JobOptions::TOKEN => $this->randomStr,
            JobOptions::INPUTS => array(
                $method => true
            )
        );
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(JobWithErrors::class, $options)
        );
        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->waitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneBy(
            array("jobToken" => $this->randomStr)
        );

        //====================================================================//
        // Verify Task
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertFalse($task->isRunning());
        Assert::assertEquals($finished, $task->isFinished());
        Assert::assertNotEmpty($task->getOutputs());
        Assert::assertNotEmpty($task->getStartedAt());
        Assert::assertNotEmpty($task->getFinishedAt());
        Assert::assertNotEmpty($task->getFaultStr());
        Assert::assertEquals(1, $task->getTry());
    }

    /**
     * Test of Task Exceptions Management
     *
     * @dataProvider jobsMethodsProvider
     *
     * @param string $method
     */
    public function testTaskExceptions(string $method): void
    {
        //====================================================================//
        // Build Task Options
        $options = array(
            JobOptions::TOKEN => $this->randomStr,
            JobOptions::INPUTS => array(
                $method => true
            )
        );
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(JobWithExceptions::class, $options)
        );
        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->waitUntilCompleted(2);
        //====================================================================//
        // Load a Task
        $this->entityManager->clear();
        $task = $this->tasksRepository->findOneBy(
            array("jobToken" => $this->randomStr)
        );

        //====================================================================//
        // Verify Task
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertFalse($task->isRunning());
        Assert::assertFalse($task->isFinished());
        Assert::assertNotEmpty($task->getOutputs());
        Assert::assertNotEmpty($task->getStartedAt());
        Assert::assertNotEmpty($task->getFinishedAt());
        Assert::assertNotEmpty($task->getFaultStr());
        Assert::assertEquals(1, $task->getTry());
    }

    /**
     * Test Wait Until Tasks Buffer is Empty
     *
     * @throws Exception
     */
    public function testWaitUntilTasksCompleted(): void
    {
        $manager = $this->getTasksManager();
        //====================================================================//
        // Delete All Tasks
        $this->deleteAllTasks();
        $this->deleteAllTokens();

        //====================================================================//
        // Test with no Tasks in Buffer
        Assert::assertTrue($manager->waitUntilTaskCompleted());
        Assert::assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 1 second Tasks in Buffer
        $this->addTask($this->randomStr, 1);
        Assert::assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        Assert::assertTrue($manager->waitUntilTaskCompleted());
        Assert::assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 3 second Tasks in Buffer
        $this->addTask($this->randomStr, 3);
        Assert::assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        Assert::assertFalse($manager->waitUntilTaskCompleted(1));
        Assert::assertEquals(1, $this->tasksRepository->getPendingTasksCount());
        Assert::assertTrue($manager->waitUntilTaskCompleted());
        Assert::assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 5 x 1 second Tasks in Buffer
        for ($i = 0; $i < 5; $i++) {
            $this->addTask($this->randomStr, 1);
        }
        Assert::assertEquals(5, $this->tasksRepository->getPendingTasksCount());
        Assert::assertTrue($manager->waitUntilTaskCompleted(2));
        Assert::assertEquals(0, $this->tasksRepository->getPendingTasksCount());

        //====================================================================//
        // Test with a 12 x 1 second Tasks in Buffer
        for ($i = 0; $i < 12; $i++) {
            $this->addTask($this->randomStr, 1);
        }
        Assert::assertEquals(12, $this->tasksRepository->getPendingTasksCount());
        Assert::assertFalse($manager->waitUntilTaskCompleted(1));
    }

    /**
     * Return List of Jobs Methods to Test for Exception & Errors
     *
     * @return array
     */
    public static function jobsMethodsProvider() : array
    {
        return array(
            "validate" => array("validate"    , false),
            "prepare" => array("prepare"     , false),
            "execute" => array("execute"     , false),
            "finalize" => array("finalize"    , true),
            "close" => array("close"       , true),
        );
    }

    /**
     * Add a New Test Simple Task & Run
     */
    private function addTask(string $token, int $delay = 1): void
    {
        //====================================================================//
        // Build Task Options
        $options = JobWithRandomInputs::toOptions(delay: $delay, token: $token);
        //====================================================================//
        // Start Task
        Assert::assertNotEmpty(
            $this->getTasksManager()->start(JobWithRandomInputs::class, $options)
        );
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

        Assert::assertEmpty($this->tasksRepository->findAll());

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

        Assert::assertEmpty($this->tokenRepository->findAll());

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
        do {
            usleep((int) (500 * 1E3));  // 500Ms
            $watchDog++;

            $this->entityManager->clear();
            $queue = $this->tasksRepository->getWaitingTasksCount();
            $queue += $this->tasksRepository->getActiveTasksCount();
        } while (($watchDog < (2 * $limit)) && ($queue > 0));
    }
}
