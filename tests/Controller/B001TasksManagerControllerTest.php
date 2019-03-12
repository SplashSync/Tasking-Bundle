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
use Splash\Tasking\Tests\Jobs\TestStaticJob;

/**
 * Test of Symfony Tasks Manager
 */
class B001TasksManagerControllerTest extends AbstractTestController
{
    /**
     * Test of Task Event Listener Job Validate Function
     */
    public function testJobValidate(): void
    {
        //====================================================================//
        // Test Standard Result
        $testJob = new TestJob();
        $this->assertTrue(
            $this->invokeMethod($this->tasks, "validate", array($testJob))
        );

        //====================================================================//
        // Detect Wrong Action
        $testJob->setInputs(array("Error-Wrong-Action" => true));
        $this->assertFalse(
            $this->invokeMethod($this->tasks, "validate", array($testJob))
        );

        //====================================================================//
        // Detect Wrong Priority
        $testJob->setInputs(array("Error-Wrong-Priority" => true));
        $this->assertFalse(
            $this->invokeMethod($this->tasks, "validate", array($testJob))
        );
    }

    /**
     * Test of Task Event Listener Job Validate Function
     */
    public function testJobPrepare(): void
    {
        //====================================================================//
        // Convert Generic Job to Task
        $job = new TestJob();
        $task = $this->invokeMethod($this->tasks, "prepare", array($job));

        //====================================================================//
        // Verify Generic Job Result
        $this->assertInstanceOf(Task::class, $task);
        $this->assertNotEmpty($task->getName());
        $this->assertEquals($task->getJobClass(), "\\".get_class($job));
        $this->assertEquals($task->getJobInputs(), $job->__get("inputs"));
        $this->assertEquals($task->getJobPriority(), $job->getPriority());
        $this->assertEquals($task->getJobToken(), $job->getToken());
        $this->assertEquals($task->getSettings(), $job->getSettings());
        $this->assertEquals($task->getJobIndexKey1(), $job->getIndexKey1());
        $this->assertEquals($task->getJobIndexKey2(), $job->getIndexKey2());
        $this->assertFalse($task->isRunning());
        $this->assertFalse($task->isFinished());
        $this->assertEquals(0, $task->getTry());
        $this->assertEmpty($task->getFaultStr());
        $this->assertNotEmpty($task->getDiscriminator());

        //====================================================================//
        // Convert Static Job to Task
        $staticJob = new TestStaticJob();
        $staticTask = $this->invokeMethod($this->tasks, "prepare", array($staticJob));

        //====================================================================//
        // Verify Static Job Result
        $this->assertInstanceOf(Task::class, $staticTask);
        $this->assertTrue($staticTask->isStaticJob());
        $this->assertNotEmpty($staticTask->getJobFrequency());
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
            $this->dispatcher->dispatch("tasking.add", $job);
        }
        //====================================================================//
        //Verify Only One Task Added
        $this->assertEquals(1, $this->tasksRepository->getWaitingTasksCount($token));
        //====================================================================//
        // Finished Tasks
        $this->tasksRepository->clean(0);
    }
}
