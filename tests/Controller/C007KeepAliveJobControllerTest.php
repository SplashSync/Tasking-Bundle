<?php

/*
 *  Copyright (C) BadPixxel <www.badpixxel.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace BadPixxel\Tasking\Tests\Controller;

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Entity\Worker;
use BadPixxel\Tasking\Tests\Bundle\Jobs\KeepAliveJob;
use DateTime;
use Exception;
use PHPUnit\Framework\Assert;

/**
 * Test of a Long Job Emitting Keep-Alive Signals
 */
class C007KeepAliveJobControllerTest extends AbstractTestController
{
    /**
     * A Job Running Longer than Workers Liveness Delay Stays Alive
     *
     * Its worker is never considered as dead, the task is neither restarted nor replayed.
     *
     * @throws Exception
     */
    public function testKeepAliveJobIsNotRestarted(): void
    {
        $livenessDelay = Worker::getLivenessDelay();
        $refreshDelay = $this->getConfiguration()->getWorkerRefreshDelay();
        //====================================================================//
        // Start a Job Running Longer than Workers Liveness Delay
        $task = $this->getTasksManager()->start(
            KeepAliveJob::class,
            KeepAliveJob::toOptions(duration: $livenessDelay + 10, token: self::randomStr())
        );
        Assert::assertInstanceOf(Task::class, $task);
        $taskId = (int) $task->getId();
        //====================================================================//
        // Wait for Task Startup, then Longer than Workers Liveness Delay
        $this->waitUntilRunning($taskId, 30);
        sleep($livenessDelay + 3);
        //====================================================================//
        // Verify Task is Still Running, Started Once & Recently Seen Alive
        $running = $this->loadTask($taskId);
        Assert::assertTrue($running->isRunning());
        Assert::assertSame(1, $running->getTry());
        Assert::assertGreaterThanOrEqual(
            (new DateTime())->getTimestamp() - 2 * $refreshDelay,
            (int) $running->getLastAliveAtTimeStamp()
        );
        //====================================================================//
        // Verify All Running Workers are Alive, Including the Busy One
        /** @var Worker[] $workers */
        $workers = $this->workersRepository->findBy(array("running" => 1));
        Assert::assertNotEmpty($workers);
        foreach ($workers as $worker) {
            Assert::assertTrue($worker->isRunning(), "Worker ".$worker->getProcess()." considered as dead");
        }
        //====================================================================//
        // Wait for Job Finished & Verify it Succeeded on First Try
        $finished = $this->waitUntilStopped($taskId, 30);
        Assert::assertTrue($finished->isFinished());
        Assert::assertSame(1, $finished->getTry());
        Assert::assertEmpty($finished->getFaultStr());
    }

    /**
     * Wait Until a Task is Flagged as Running
     *
     * @param int $taskId  Task Entity ID
     * @param int $timeout Max Delay in Seconds
     *
     * @return void
     */
    private function waitUntilRunning(int $taskId, int $timeout): void
    {
        for ($second = 0; $second < $timeout; $second++) {
            if ($this->loadTask($taskId)->isRunning()) {
                return;
            }
            sleep(1);
        }
        Assert::fail("Keep-Alive task never started");
    }

    /**
     * Wait Until a Task is No More Flagged as Running
     *
     * @param int $taskId  Task Entity ID
     * @param int $timeout Max Delay in Seconds
     *
     * @return Task
     */
    private function waitUntilStopped(int $taskId, int $timeout): Task
    {
        for ($second = 0; $second < $timeout; $second++) {
            $task = $this->loadTask($taskId);
            if (!$task->isRunning()) {
                return $task;
            }
            sleep(1);
        }
        Assert::fail("Keep-Alive task never stopped");
    }

    /**
     * Reload a Task from Database
     *
     * @param int $taskId Task Entity ID
     *
     * @return Task
     */
    private function loadTask(int $taskId): Task
    {
        $this->entityManager->clear();
        $task = $this->tasksRepository->find($taskId);
        Assert::assertInstanceOf(Task::class, $task);

        return $task;
    }
}
