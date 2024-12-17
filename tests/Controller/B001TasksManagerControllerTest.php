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
use BadPixxel\Tasking\Dictionary\TaskPriority;
use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Tests\Bundle\Jobs\SimpleJob;
use BadPixxel\Tasking\Tests\Bundle\Jobs\StaticJob;
use Exception;
use PHPUnit\Framework\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Test of Symfony Tasks Manager
 */
class B001TasksManagerControllerTest extends AbstractTestController
{
    /**
     * Test of Job with Invalid Action Throw an Exception
     *
     * @throws Exception
     */
    public function testJobWithInvalidAction(): void
    {
        $tasksManager = $this->getTasksManager();

        $this->expectException(InvalidArgumentException::class);
        $tasksManager->start(
            SimpleJob::class,
            array(JobOptions::ACTION => "this-action-does-not-exists")
        );
    }

    /**
     * Test of Job with Invalid Priority Throw an Exception
     *
     * @throws Exception
     */
    public function testJobWithInvalidPriority(): void
    {
        $tasksManager = $this->getTasksManager();

        $this->expectException(InvalidArgumentException::class);
        $tasksManager->start(
            SimpleJob::class,
            array(JobOptions::PRIORITY => -666)
        );
    }

    /**
     * Test of Task Event Listener Job Validate Function
     *
     * @throws Exception
     */
    public function testJobPrepare(): void
    {
        $taskFactory = $this->getTaskFactory();
        //====================================================================//
        // Convert Generic Job to Task
        $task = $taskFactory->fromConfiguration(SimpleJob::class, array());

        //====================================================================//
        // Resolve Job Inputs
        Assert::assertInstanceOf(
            SimpleJob::class,
            $jobService = $this->getContainer()->get(SimpleJob::class)
        );

        //====================================================================//
        // Verify Generic Job Result
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertNotEmpty($task->getName());
        Assert::assertEquals(SimpleJob::class, $task->getJobClass());
        Assert::assertEquals($jobService->resolveInputs(array()), $task->getJobInputs());
        Assert::assertEquals(TaskPriority::NORMAL, $task->getJobPriority());
        Assert::assertEquals(SimpleJob::class, $task->getJobToken());
        Assert::assertEquals($task->getSettings(), $jobService->resolveSettings(array()));
        Assert::assertNull($task->getJobIndexKey1());
        Assert::assertNull($task->getJobIndexKey2());
        Assert::assertFalse($task->isRunning());
        Assert::assertFalse($task->isFinished());
        Assert::assertEquals(0, $task->getTry());
        Assert::assertEmpty($task->getFaultStr());
        Assert::assertNotEmpty($task->getDiscriminator());

        //====================================================================//
        // Convert Static Job to Task
        $staticTask = $taskFactory->fromConfiguration(StaticJob::class, array());

        //====================================================================//
        // Verify Static Job Result
        Assert::assertInstanceOf(Task::class, $staticTask);
        Assert::assertTrue($staticTask->isStaticJob());
        Assert::assertNotEmpty($staticTask->getJobFrequency());
    }

    /**
     * Test Similar Tasks are not Added Twice
     */
    public function testNoDuplicateTask(): void
    {
        $nbTasks = 10;
        //====================================================================//
        // Generate a Random Token Name
        $token = self::randomStr();
        //====================================================================//
        // Add Task To List
        for ($i = 0; $i < $nbTasks; $i++) {
            //====================================================================//
            // Build Task Options
            $options = SimpleJob::toOptions(delay: 0, delayMs: 100, token: $token);
            //====================================================================//
            // Start Task
            Assert::assertNotEmpty(
                $this->getTasksManager()->start(SimpleJob::class, $options)
            );
        }
        //====================================================================//
        //Verify Only One Task Added
        Assert::assertEquals(1, $this->tasksRepository->getWaitingTasksCount($token));
        //====================================================================//
        // Finished Tasks
        $this->tasksRepository->clean(0);
    }
}
