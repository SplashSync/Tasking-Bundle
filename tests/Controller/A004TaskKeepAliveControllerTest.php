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

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Entity\Token;
use BadPixxel\Tasking\Entity\Worker;
use BadPixxel\Tasking\Events\TaskKeepAliveEvent;
use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use BadPixxel\Tasking\Tests\Bundle\Jobs\JobWithRandomInputs;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test of Task Keep-Alive Signals
 */
class A004TaskKeepAliveControllerTest extends AbstractTestController
{
    /**
     * A Keep-Alive Signal Outside a Running Task is a No-Op
     *
     * @throws Exception
     */
    public function testKeepAliveOutsideTaskIsNoOp(): void
    {
        Assert::assertFalse($this->getStatusMonitor()->isJobRunning());
        //====================================================================//
        // Dispatch Keep-Alive Signal
        $event = $this->getDispatcher()->dispatch(new TaskKeepAliveEvent());
        //====================================================================//
        // Verify Nothing was Granted
        Assert::assertFalse($event->isAccepted());
        Assert::assertFalse($this->getStatusMonitor()->keepAlive(10));
    }

    /**
     * A Keep-Alive Signal Extends Current Job, Capped by Token Lifetime
     *
     * @throws Exception
     */
    public function testKeepAliveIsCappedByTokenLifetime(): void
    {
        $monitor = $this->getStatusMonitor();
        $watchdogDelay = $this->getConfiguration()->getWorkerWatchdogDelay();

        try {
            //====================================================================//
            // Simulate a Running Job with a Token
            $monitor->setJobStarted();
            $monitor->setTokenAcquired($this->randomStr);
            //====================================================================//
            // Default Request is Granted & Resets the Watchdog
            $event = $this->getDispatcher()->dispatch(new TaskKeepAliveEvent());
            Assert::assertTrue($event->isAccepted());
            Assert::assertGreaterThanOrEqual($watchdogDelay - 1, $monitor->getWatchdogLifetime());
            //====================================================================//
            // A Request Longer than the Watchdog Extends the Watchdog
            Assert::assertTrue($monitor->keepAlive(2 * $watchdogDelay));
            Assert::assertGreaterThanOrEqual(2 * $watchdogDelay - 1, $monitor->getWatchdogLifetime());
            //====================================================================//
            // A Request Exceeding the Token Lifetime is Rejected
            $tooLong = $this->getConfiguration()->getTokenSelfReleaseDelay() + 60;
            $event = $this->getDispatcher()->dispatch(new TaskKeepAliveEvent($tooLong));
            Assert::assertFalse($event->isAccepted());
        } finally {
            //====================================================================//
            // Restore Test Process State
            $monitor->setJobFinished();
            $monitor->setTokenReleased();
            set_time_limit(0);
        }
    }

    /**
     * Worker Liveness Threshold is Derived from Configuration
     */
    public function testWorkerLivenessDelay(): void
    {
        Assert::assertSame(
            $this->getConfiguration()->getWorkerWatchdogDelay() + $this->getConfiguration()->getWorkerRefreshDelay(),
            Worker::getLivenessDelay()
        );
    }

    /**
     * Running Tasks are Replayed on their Last Keep-Alive, not on their Start Date
     *
     * @throws Exception
     */
    public function testReplayRuleUsesLastAlive(): void
    {
        $token = self::randomStr();
        $options = $this->getConfiguration()->getTasksSearchOptions();
        $errorDelay = $this->getConfiguration()->getTasksErrorDelay();
        //====================================================================//
        // Create a Task, Lock its Token so Background Workers Never Pick it
        $task = $this->getTasksManager()->start(
            JobWithRandomInputs::class,
            JobWithRandomInputs::toOptions(delay: 1, token: $token),
            false
        );
        Assert::assertInstanceOf(Task::class, $task);
        Assert::assertInstanceOf(Token::class, $this->tokenRepository->acquire($token));
        $taskId = (int) $task->getId();
        //====================================================================//
        // Simulate a Task Started & Last Seen Alive Long Ago, Still Flagged as Running
        $task = $this->tasksRepository->find($taskId);
        Assert::assertInstanceOf(Task::class, $task);
        $task->setRunning(true);
        $task->setTry(1);
        $task->setStartedAt(new DateTime("-".($errorDelay + 60)." Seconds"));
        $this->entityManager->flush();
        Assert::assertSame($task->getStartedAt()?->getTimestamp(), $task->getLastAliveAtTimeStamp());
        //====================================================================//
        // Silent for too Long => Task is in Error & Replayed
        Assert::assertSame($taskId, $this->tasksRepository->getNextTask($options, $token)?->getId());
        //====================================================================//
        // Recently Kept Alive => Task is NOT Replayed
        $this->tasksRepository->touchAlive($taskId, (new DateTime())->getTimestamp());
        $this->entityManager->clear();
        Assert::assertNull($this->tasksRepository->getNextTask($options, $token));
        //====================================================================//
        // No Keep-Alive Value (Tasks Started Before Upgrade) => Fallback on Start Date
        $this->getOrmManager()->getConnection()->executeStatement(
            "UPDATE system__tasks SET LastAliveAtTimeStamp = NULL WHERE id = ?",
            array($taskId)
        );
        $this->entityManager->clear();
        Assert::assertSame($taskId, $this->tasksRepository->getNextTask($options, $token)?->getId());
        //====================================================================//
        // Cleanup
        $this->entityManager->clear();
        $loaded = $this->tasksRepository->find($taskId);
        Assert::assertInstanceOf(Task::class, $loaded);
        $this->entityManager->remove($loaded);
        $this->entityManager->flush();
        Assert::assertTrue($this->tokenRepository->delete($token));
    }

    /**
     * Get Event Dispatcher from Current Kernel
     *
     * Parent helper caches the dispatcher of the first booted kernel: its listeners
     * would not share the Status Monitor of the current kernel.
     *
     * @return EventDispatcherInterface
     */
    private function getDispatcher(): EventDispatcherInterface
    {
        $service = $this->getContainer()->get(EventDispatcherInterface::class);
        Assert::assertInstanceOf(EventDispatcherInterface::class, $service);

        return $service;
    }

    /**
     * Get Status Monitor Service
     *
     * @return StatusMonitor
     */
    private function getStatusMonitor(): StatusMonitor
    {
        $service = $this->getContainer()->get(StatusMonitor::class);
        Assert::assertInstanceOf(StatusMonitor::class, $service);

        return $service;
    }

    /**
     * Get Entity Manager as an Orm Manager
     *
     * @return EntityManagerInterface
     */
    private function getOrmManager(): EntityManagerInterface
    {
        Assert::assertInstanceOf(EntityManagerInterface::class, $this->entityManager);

        return $this->entityManager;
    }
}
