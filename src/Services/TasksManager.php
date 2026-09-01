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

namespace BadPixxel\Tasking\Services;

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Events\CheckSupervisorEvent;
use BadPixxel\Tasking\Events\InsertTaskEvent;
use BadPixxel\Tasking\Events\StartTaskEvent;
use BadPixxel\Tasking\Helper\Timer;
use BadPixxel\Tasking\Services\Tasks\TaskFactory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tasks Management Service
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 * @SuppressWarnings(ExcessiveClassComplexity)
 */
class TasksManager implements EventSubscriberInterface
{
    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @throws Exception
     */
    public function __construct(
        private readonly Configuration            $configuration,
        private readonly TaskFactory              $taskFactory,
        private readonly TokenManager             $token,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface          $logger,
    ) {
        //====================================================================//
        // Ensure Configuration is Ready
        $this->configuration->isReady();
    }

    //====================================================================//
    //  Event Subscriber
    //====================================================================//

    /**
     * Register Subscribed Events Actions
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            StartTaskEvent::class => "onStartEvent",
            InsertTaskEvent::class => "onInsertEvent",
        );
    }

    //====================================================================//
    //  Generic Tasks Management
    //====================================================================//

    /**
     * Start a New Task on Scheduler
     */
    public function start(string $serviceIdOrClass, array $options, bool $check = true): ?Task
    {
        //====================================================================//
        // Prepare Task from received Configuration
        $task = $this->taskFactory->fromConfiguration($serviceIdOrClass, $options);
        if (!$task) {
            $this->logger->error("Tasks Manager: Invalid Job Received >> Rejected");

            return null;
        }
        //==============================================================================
        // Validate Token Before Task Insert
        //==============================================================================
        $this->token->validate($task);

        //====================================================================//
        // Add Task To Queue
        try {
            $this->insert($task);
        } catch (NoResultException|NonUniqueResultException $e) {
            $this->logger->error("Tasks Manager: ".$e->getMessage());

            return null;
        }

        //==============================================================================
        // Ensure Supervisor is Running
        //==============================================================================
        if ($check) {
            $this->checkSupervisor();
        }

        return $task;
    }

    /**
     * Add a New Task on Scheduler & Check Supervisor
     */
    public function onStartEvent(StartTaskEvent $event): bool
    {
        return (bool) $this->start($event->getSubject(), $event->getArguments());
    }

    /**
     * Only Add a New Task on Scheduler
     */
    public function onInsertEvent(InsertTaskEvent $event): bool
    {
        return (bool) $this->start($event->getSubject(), $event->getArguments(), false);
    }

    /**
     * Start Tasking Supervisor on This Machine
     */
    public function checkSupervisor(): void
    {
        //====================================================================//
        // Dispatch Task Check Event
        $this->dispatcher->dispatch(new CheckSupervisorEvent());
    }

    /**
     * Retrieve Next Available Task from database
     *
     * @param null|string $currentToken
     * @param bool        $staticMode
     *
     * @throws Exception
     * @throws NonUniqueResultException
     *
     * @return null|Task
     */
    public function next(?string $currentToken, bool $staticMode): ?Task
    {
        return  $this->configuration->getTasksRepository()->getNextTask(
            $this->configuration->getTasksSearchOptions(),
            $currentToken,
            $staticMode
        );
    }

    /**
     * Clean Task Buffer to remove old Finished Tasks
     *
     * @throws Exception
     *
     * @return int
     */
    public function cleanUp() : int
    {
        //====================================================================//
        // Delete Old Tasks from Database
        $cleanCounter = $this->configuration
            ->getTasksRepository()
            ->clean($this->configuration->getTasksDeleteDelay())
        ;
        //====================================================================//
        // User Information
        if ($cleanCounter > 0) {
            $this->logger->info('Task Manager: Cleaned '.$cleanCounter.' Tasks');
        }
        //====================================================================//
        // Delete Old Token from Database
        $cleanCounter = $this->configuration
            ->getTokenRepository()
            ->clean($this->configuration->getTokenDeleteDelay())
        ;

        //====================================================================//
        // Reload Repository Data
        $this->configuration->getEntityManager()->clear();

        return $cleanCounter;
    }

    /**
     * Wait Until All Tasks are Completed
     *
     * @param int         $timeout Stagnation watchdog in seconds: max time without any
     *                             change in the pending tasks count. It is reset on every
     *                             change, so it does not bound the total wait. An absolute
     *                             cap of 10 x timeout also applies.
     * @param null|string $token   Filter on a specific token Name
     * @param null|string $md5     Filter on a specific Discriminator
     * @param null|string $key1    Your Custom Index Key 1
     * @param null|string $key2    Your Custom Index Key 2
     *
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return bool True if All Tasks Completed, False if the Watchdog
     *              or the Absolute Cap was Reached
     */
    public function waitUntilTaskCompleted(
        int $timeout = 10,
        string $token = null,
        string $md5 = null,
        string $key1 = null,
        string $key2 = null
    ): bool {
        //==============================================================================
        // Init Time Counters
        $msSteps = 10;                  // 10 ms
        $msTimeout = 1E3 * $timeout;    // Timeout in µs
        //==============================================================================
        // Add 200 ms pause to Ensure Task Started
        Timer::msSleep(200);
        //==============================================================================
        // Init Watchdogs Timers
        $watchdog = 0;
        $absWatchdog = 0;
        //==============================================================================
        // Init Counters
        $lastPending = 1;
        //====================================================================//
        // Loop While Tasks are Running
        do {
            //==============================================================================
            // Sampling Pause
            Timer::msSleep($msSteps);
            //==============================================================================
            // Get Number of Pending Tasks
            $pending = $this->configuration->getTasksRepository()->getPendingTasksCount($token, $md5, $key1, $key2);
            //==============================================================================
            // Check If Tasks Completed
            if ((0 == $pending) && (0 == $lastPending)) {
                return true;
            }
            //==============================================================================
            // Increment Tasks Execution WatchDogs
            $watchdog = ($pending == $lastPending)
                ? $watchdog + $msSteps
                : 0;
            //==============================================================================
            // Increment Absolute WatchDogs
            $absWatchdog += $msSteps;
            //==============================================================================
            // Store Last Pending Task Count
            $lastPending = $pending;
        } while (($watchdog < $msTimeout) && ($absWatchdog < (10 * $msTimeout)));

        return false;
    }

    //====================================================================//
    //  PRIVATE - Tasks Management
    //====================================================================//

    /**
     * Insert Tasks in DataBase
     *
     * @param Task $task Task Item to Insert
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function insert(Task $task): void
    {
        //====================================================================//
        // Ensure no Similar Task Already Waiting
        $count = $this->configuration->getTasksRepository()->getWaitingTasksCount(
            $task->getJobToken(),
            $task->getDiscriminator(),
            $task->getJobIndexKey1(),
            $task->getJobIndexKey2()
        );
        if ($count > 0) {
            return;
        }
        //====================================================================//
        // Persist New Task to Db
        $this->configuration->getEntityManager()->persist($task);
        $this->configuration->getEntityManager()->flush();
    }
}
