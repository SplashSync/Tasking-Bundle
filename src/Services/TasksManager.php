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

namespace Splash\Tasking\Services;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Events\AddEvent;
use Splash\Tasking\Events\CheckEvent;
use Splash\Tasking\Events\InsertEvent;
use Splash\Tasking\Events\StaticTasksListingEvent;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractStaticJob;
use Splash\Tasking\Tools\Timer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tasks Management Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TasksManager
{
    /**
     * @var TasksManager
     */
    private static TasksManager $staticInstance;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @throws Exception
     */
    public function __construct(
        private Configuration $configuration,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
        private JobsManager $jobs,
        private TokenManager $token
    ) {
        //====================================================================//
        // Ensure Configuration is Ready
        $this->configuration->isReady();
        //==============================================================================
        // Store Static Instance for Access as Static
        self::$staticInstance = $this;
    }

    //====================================================================//
    //  Generic Tasks Management
    //====================================================================//

    /**
     * Start Tasking Supervisor on This Machine
     *
     * @return void
     */
    public static function check(): void
    {
        //====================================================================//
        // Dispatch Task Check Event
        self::dispatch(new CheckEvent());
    }

    /**
     * Add Tasks in DataBase
     *
     * @param AbstractJob $job An Object Extending Base Job Object
     *
     * @return null|AddEvent
     */
    public static function add(AbstractJob $job): ?AddEvent
    {
        //====================================================================//
        // Dispatch Task Added Event
        $event = self::dispatch(new AddEvent($job));

        return ($event instanceof AddEvent) ? $event : null;
    }

    /**
     * Insert Tasks in DataBase
     *
     * @param AbstractJob $job An Object Extending Base Job Object
     *
     * @return null|InsertEvent
     */
    public static function addNoCheck(AbstractJob $job): ?InsertEvent
    {
        //====================================================================//
        // Dispatch Task Added Event
        $event = self::dispatch(new InsertEvent($job));

        return ($event instanceof InsertEvent) ? $event : null;
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
        return  Configuration::getTasksRepository()->getNextTask(
            Configuration::getTasksConfiguration(),
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
        $cleanCounter = Configuration::getTasksRepository()->clean(Configuration::getTasksDeleteDelay());
        //====================================================================//
        // User Information
        if ($cleanCounter > 0) {
            $this->logger->info('Task Manager: Cleaned '.$cleanCounter.' Tasks');
        }
        //====================================================================//
        // Delete Old Token from Database
        $cleanCounter = Configuration::getTokenRepository()->clean(Configuration::getTokenDeleteDelay());

        //====================================================================//
        // Reload Repository Data
        Configuration::getEntityManager()->clear();

        return $cleanCounter;
    }

    /**
     * Wait Until All Tasks are Completed
     *
     * @param int         $timeout TimeOut in Seconds
     * @param null|string $token   Filter on a specific token Name
     * @param null|string $md5     Filter on a specific Discriminator
     * @param null|string $key1    Your Custom Index Key 1
     * @param null|string $key2    Your Custom Index Key 2
     *
     * @throws Exception
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return bool True if Ok, False if Exited on Timout
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
            $pending = Configuration::getTasksRepository()->getPendingTasksCount($token, $md5, $key1, $key2);
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
    //  Static Tasks Management
    //====================================================================//

    /**
     *  Initialize Static Task Buffer in Database
     *  => Tasks are Loaded from Parameters
     *  => Or by registering Event dispatcher
     *
     * @throws Exception
     *
     * @return $this
     */
    public function loadStaticTasks(): self
    {
        //====================================================================//
        // Get List of Static Tasks to Setup
        $staticJobs = $this->jobs->getStaticJobs();
        //====================================================================//
        // Get List of Static Tasks in Database
        $database = Configuration::getTasksRepository()->getStaticTasks();
        //====================================================================//
        // Loop on All Database Tasks to Identify Static Tasks
        foreach ($database as $task) {
            //====================================================================//
            // Try to Identify Task in Static Task List
            foreach ($staticJobs as $index => $staticTask) {
                //====================================================================//
                // If Tasks Are Similar => Delete From List
                if ($this->compareStaticTask($staticTask, $task)) {
                    unset($staticJobs[$index]);
                }
            }
            //====================================================================//
            // Task Not to Run (Doesn't Exists) => Delete from Database
            Configuration::getEntityManager()->remove($task);
            Configuration::getEntityManager()->flush();
        }
        //====================================================================//
        // Loop on Tasks to Add it On Database
        foreach ($staticJobs as $staticTask) {
            $this->onAddAction(new GenericEvent($staticTask));
        }

        return $this;
    }

    //====================================================================//
    //  Tasking Events Actions
    //====================================================================//

    /**
     * Add a New Task on Scheduler
     *
     * @param GenericEvent $event
     *
     * @throws Exception
     *
     * @return bool
     */
    public function onAddAction(GenericEvent $event): bool
    {
        $job = $event->getSubject();
        //====================================================================//
        // Validate Job
        if (!($job instanceof AbstractJob)) {
            $this->logger->error("Tasks Manager: Invalid Job Received >> Rejected");

            return false;
        }
        if (!$this->validate($job)) {
            $job->setInputs(array("error" => "Invalid Job: Rejected"));
            $this->logger->error("Tasks Manager: Invalid Job Received >> Rejected");

            return false;
        }
        //====================================================================//
        // Prepare Task From Job Class
        $task = $this->prepare($job);
        //====================================================================//
        // Add Task To Queue
        try {
            $this->insert($task);
        } catch (NoResultException|NonUniqueResultException $e) {
            $this->logger->error("Tasks Manager: ".$e->getMessage());

            return false;
        }

        return true;
    }

    //====================================================================//
    //  PRIVATE - Jobs Validation Function
    //====================================================================//

    /**
     * Verify given Job before being added to scheduler
     *
     * @param AbstractJob $job An Object Extending Base Job Object
     *
     * @return bool
     */
    private function validate(AbstractJob $job): bool
    {
        //====================================================================//
        // Job Class and Action are not empty
        if (strlen($job->getAction()) < 3) {
            return false;
        }
        //====================================================================//
        // Job Action Method Exists
        if (!method_exists($job, $job->getAction())) {
            return false;
        }
        //====================================================================//
        // Job Priority is Valid
        if ($job->getPriority() < 0) {
            return false;
        }
        //====================================================================//
        // Validate Static & Batch Jobs Specific Options
        return $this->validateStaticJob($job) && $this->validateBatchJob($job);
    }

    /**
     * Take Given Job Parameters and convert it on a Task for Storage
     *
     * @param AbstractJob $job User Job Object
     *
     * @throws Exception
     *
     * @return Task
     */
    private function prepare(AbstractJob $job): Task
    {
        //====================================================================//
        // Create a New Task
        $task = new Task();
        //====================================================================//
        // Setup Task Parameters
        $task
            ->setName(get_class($job)."::".$job->getAction())
            ->setJobClass(get_class($job))
            ->setJobAction($job->getAction())
            ->setJobInputs($job->getRawInputs())
            ->setJobPriority($job->getPriority())
            ->setJobToken($job->getToken())
            ->setSettings($job->getSettings())
            ->setJobIndexKey1($job->getIndexKey1())
            ->setJobIndexKey2($job->getIndexKey2())
        ;
        //====================================================================//
        // If is a Static Job
        //====================================================================//
        if (is_subclass_of($job, AbstractStaticJob::class)) {
            $task
                ->setName("[S] ".$task->getName())
                ->setJobIsStatic(true)
                ->setJobFrequency($job->getFrequency());
        }

        //====================================================================//
        // If is a Static Job
        //====================================================================//
        if (is_subclass_of($job, AbstractBatchJob::class)) {
            $task
                ->setName("[B] ".$task->getName());
        }

        //====================================================================//
        // Update Task Discriminator
        $task->updateDiscriminator();

        //==============================================================================
        // Validate Token Before Task Insert
        //==============================================================================
        $this->token->validate($task);

        return $task;
    }

    /**
     * Verify given Static Job before being added to scheduler
     *
     * @param AbstractJob $job An Object Extending Base Job Object
     *
     * @return bool
     */
    private function validateStaticJob(AbstractJob $job): bool
    {
        //====================================================================//
        // If is a Static Job
        //====================================================================//
        if (is_subclass_of($job, AbstractStaticJob::class)) {
            if ($job->getFrequency() <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verify given Batch Job before being added to scheduler
     *
     * @param AbstractJob $job An Object Extending Base Job Object
     *
     * @return bool
     */
    private function validateBatchJob(AbstractJob $job): bool
    {
        //====================================================================//
        // If is a Batch Job
        //====================================================================//
        if (is_subclass_of($job, AbstractBatchJob::class)) {
            return $job::validateBatchJobActions();
        }

        return true;
    }

    //====================================================================//
    //  PRIVATE - Static Tasks Management
    //====================================================================//

    /**
     * Insert Tasks in DataBase
     *
     * @param Task $task Task Item to Insert
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function insert(Task $task): void
    {
        //====================================================================//
        // Ensure no Similar Task Already Waiting
        $count = Configuration::getTasksRepository()->getWaitingTasksCount(
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
        Configuration::getEntityManager()->persist($task);
        Configuration::getEntityManager()->flush();
    }

    /**
     * Identify Static Task in Parameters
     *
     * @param AbstractStaticJob $staticJob
     * @param Task              $task
     *
     * @return bool true if Static Tasks are Similar
     */
    private function compareStaticTask(AbstractStaticJob $staticJob, Task $task) : bool
    {
        //====================================================================//
        // Filter by Class Name
        if (get_class($staticJob) != $task->getJobClass()) {
            return false;
        }
        //====================================================================//
        // Filter by Token
        if ($staticJob->getToken() != $task->getJobToken()) {
            return false;
        }
        //====================================================================//
        // Filter by Frequency
        if ($staticJob->getFrequency() != $task->getJobFrequency()) {
            return false;
        }
        //====================================================================//
        // Filter by Inputs
        if (serialize($staticJob->getInputs()) !== serialize($task->getJobInputs())) {
            return false;
        }

        return true;
    }

    /**
     * Dispatch an Event with Args Detection
     *
     * @param GenericEvent $event
     *
     * @return null|AddEvent|CheckEvent|InsertEvent|StaticTasksListingEvent
     */
    private static function dispatch(GenericEvent $event): ?GenericEvent
    {
        try {
            $reflection = new ReflectionMethod(self::$staticInstance->dispatcher, "dispatch");
            $args = array();
            foreach ($reflection->getParameters() as $param) {
                if ("event" == $param->getName()) {
                    $args[] = $event;
                }
                if ("eventName" == $param->getName()) {
                    $args[] = get_class($event);
                }
            }
            $response = $reflection->invokeArgs(self::$staticInstance->dispatcher, $args);
        } catch (ReflectionException) {
            return null;
        }

        if (($response instanceof AddEvent) || ($response instanceof CheckEvent)) {
            return $response;
        }
        if (($response instanceof InsertEvent) || ($response instanceof StaticTasksListingEvent)) {
            return $response;
        }

        return null;
    }
}
