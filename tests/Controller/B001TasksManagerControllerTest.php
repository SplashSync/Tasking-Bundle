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

namespace Splash\Tasking\Tests\Controller;

use Exception;
use PHPUnit\Framework\Assert;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestJob;
use Splash\Tasking\Tests\Jobs\TestStaticJob;

/**
 * Test of Symfony Tasks Manager
 */
class B001TasksManagerControllerTest extends AbstractTestController
{
    /**
     * Test of Task Event Listener Job Validate Function
     *
     * @throws Exception
     */
    public function testJobValidate(): void
    {
        $tasksManager = $this->getTasksManager();
        //====================================================================//
        // Test Standard Result
        $testJob = new TestJob();
        Assert::assertTrue(
            $this->invokeMethod($tasksManager, "validate", array($testJob))
        );

        //====================================================================//
        // Detect Wrong Action
        $testJob->setInputs(array("Error-Wrong-Action" => true));
        Assert::assertFalse(
            $this->invokeMethod($tasksManager, "validate", array($testJob))
        );

        //====================================================================//
        // Detect Wrong Priority
        $testJob->setInputs(array("Error-Wrong-Priority" => true));
        Assert::assertFalse(
            $this->invokeMethod($tasksManager, "validate", array($testJob))
        );
    }

    /**
     * Test of Task Event Listener Job Validate Function
     *
     * @throws Exception
     */
    public function testJobPrepare(): void
    {
        $tasksManager = $this->getTasksManager();
        //====================================================================//
        // Convert Generic Job to Task
        $job = new TestJob();
        $task = $this->invokeMethod($tasksManager, "prepare", array($job));

        //====================================================================//
        // Verify Generic Job Result
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertNotEmpty($task->getName());
        Assert::assertEquals($task->getJobClass(), get_class($job));
        Assert::assertEquals($task->getJobInputs(), $job->getRawInputs());
        Assert::assertEquals($task->getJobPriority(), $job->getPriority());
        Assert::assertEquals($task->getJobToken(), $job->getToken());
        Assert::assertEquals($task->getSettings(), $job->getSettings());
        Assert::assertEquals($task->getJobIndexKey1(), $job->getIndexKey1());
        Assert::assertEquals($task->getJobIndexKey2(), $job->getIndexKey2());
        Assert::assertFalse($task->isRunning());
        Assert::assertFalse($task->isFinished());
        Assert::assertEquals(0, $task->getTry());
        Assert::assertEmpty($task->getFaultStr());
        Assert::assertNotEmpty($task->getDiscriminator());

        //====================================================================//
        // Convert Static Job to Task
        $staticJob = new TestStaticJob();
        $staticTask = $this->invokeMethod($tasksManager, "prepare", array($staticJob));

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
            // Create a New Job
            $job = (new TestJob())
                ->setToken($token)
                ->setInputs(array( "Delay-Ms" => 100 ))
            ;
            //====================================================================//
            // Add Job to Queue
            $job->add();
        }
        //====================================================================//
        //Verify Only One Task Added
        Assert::assertEquals(1, $this->tasksRepository->getWaitingTasksCount($token));
        //====================================================================//
        // Finished Tasks
        $this->tasksRepository->clean(0);
    }
}
