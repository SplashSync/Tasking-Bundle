<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Services;

use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Events\AddEvent;
use Splash\Tasking\Events\StaticTasksListingEvent;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractStaticJob;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Tools\Timer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tasks Management Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TasksManager
{
    //==============================================================================
    //  Variables Definition
    //==============================================================================

    /**
     * Doctrine Entity Manager
     *
     * @var EntityManagerInterface
     */
    public $entityManager;

    /**
     * Tasking Service Configuration Array
     *
     * @var ArrayObject
     */
    protected $config;

    /**
     * Tasks Repository
     *
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * Token Manager
     *
     * @var TokenManager
     */
    private $token;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Symfony Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @param EntityManagerInterface   $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param TokenManager             $token
     * @param array                    $config
     */
    public function __construct(EntityManagerInterface $doctrine, EventDispatcherInterface $dispatcher, LoggerInterface $logger, TokenManager $token, array $config)
    {
        //====================================================================//
        // Link to entity manager Service
        $this->entityManager = $doctrine;
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
        //====================================================================//
        // Link to Symfony Event Dispatcher
        $this->dispatcher = $dispatcher;
        //====================================================================//
        // Link to Tasks Repository
        $taskRepository = $doctrine->getRepository(Task::class);
        if (!($taskRepository instanceof TaskRepository)) {
            throw new Exception("Wrong repository class");
        }
        $this->taskRepository = $taskRepository;
        //====================================================================//
        // Link to Token Manager
        $this->token = $token;
        //====================================================================//
        // Init Parameters
        $this->config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
    }

    //====================================================================//
    //  Generic Tasks Management
    //====================================================================//

    /**
     * Insert Tasks in DataBase
     *
     * @param Task $task Task Item to Insert
     */
    public function insert(Task $task): void
    {
        //====================================================================//
        // Ensure no Similar Task Already Waiting
        $count = $this->taskRepository->getWaitingTasksCount(
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
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    /**
     * Retrieve Next Available Task from database
     *
     * @param null|string $currentToken
     * @param bool        $staticMode
     *
     * @return null|Task
     */
    public function next(?string $currentToken, bool $staticMode): ?Task
    {
        return  $this->taskRepository->getNextTask(
            $this->config->tasks,
            $currentToken,
            $staticMode
        );
    }

    /**
     * Clean Task Buffer to remove old Finished Tasks
     *
     * @return int
     */
    public function cleanUp() : int
    {
        //====================================================================//
        // Delete Old Tasks from Database
        $cleanCounter = $this->taskRepository->clean($this->config->tasks['max_age']);
        //====================================================================//
        // User Information
        if ($cleanCounter > 0) {
            $this->logger->info('Task Manager: Cleaned '.$cleanCounter.' Tasks');
        }
        //====================================================================//
        // Reload Reprository Data
        $this->entityManager->clear();

        return $cleanCounter;
    }

    /**
     * Wait Until All Tasks are Completed
     *
     * @param int    $timeout TimeOut in Seconds
     * @param string $token   Filter on a specific token Name
     * @param string $md5     Filter on a specific Discriminator
     * @param string $key1    Your Custom Index Key 1
     * @param string $key2    Your Custom Index Key 2
     *
     * @return bool True if Ok, False if Exited on Timout
     */
    public function waitUntilTaskCompleted(int $timeout = 10, string $token = null, string $md5 = null, string $key1 = null, string $key2 = null): bool
    {
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
        $pending = 1;
        $lastPending = 1;

        //====================================================================//
        // Loop While Tasks are Running
        do {
            //==============================================================================
            // Sampling Pause
            Timer::msSleep($msSteps);
            //==============================================================================
            // Get Number of Pending Tasks
            $pending = $this->taskRepository->getPendingTasksCount($token, $md5, $key1, $key2);
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
     * @return $this
     */
    public function loadStaticTasks(): self
    {
        //====================================================================//
        // Get List of Static Tasks to Setup
        $staticTaskList = $this->getStaticTasks();
        //====================================================================//
        // Get List of Static Tasks in Database
        $database = $this->taskRepository->getStaticTasks();
        //====================================================================//
        // Loop on All Database Tasks to Identify Static Tasks
        foreach ($database as $task) {
            //====================================================================//
            // Try to Identify Task in Static Task List
            foreach ($staticTaskList as $index => $staticTask) {
                //====================================================================//
                // If Tasks Are Similar => Delete From List
                if ($this->compareStaticTask($staticTask, $task)) {
                    unset($staticTaskList[$index]);

                    continue;
                }
            }
            //====================================================================//
            // Task Not to Run (Doesn't Exists) => Delete from Database
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        }

        //====================================================================//
        // Loop on Tasks to Add it On Database
        foreach ($staticTaskList as $staticTask) {
            if (class_exists($staticTask["class"])) {
                $className = "\\".$staticTask["class"];
                $job = new $className();
                $job
                    ->setFrequency($staticTask["frequency"])
                    ->setToken($staticTask["token"])
                    ->setInputs($staticTask["inputs"]);

                $this->onAddAction($job);
            }
        }

        return $this;
    }

    //====================================================================//
    //  Tasking Events Actions
    //====================================================================//

    /**
     * Add a New Task on Scheduler
     *
     * @param AddEvent $event
     *
     * @return bool
     */
    public function onAddAction(AddEvent $event): bool
    {
        $job = $event->getSubject();
        //====================================================================//
        // Validate Job
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
        $this->insert($task);

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
     * Take Given Job Parameters ans convert it on a Task for Storage
     *
     * @param AbstractJob $job User Job Object
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
            ->setJobClass("\\".get_class($job))
            ->setJobAction($job->getAction())
            ->setJobInputs($job->__get("inputs"))
            ->setJobPriority($job->getPriority())
            ->setJobToken($job->getToken())
            ->setSettings($job->getSettings())
            ->setJobIndexKey1($job->getIndexKey1())
            ->setJobIndexKey2($job->getIndexKey2());

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
     * Return List of Static Tasks
     *  => Tasks are Loaded from Parameters
     *  => Or Added by registering Event dispatcher
     *
     * @return array
     */
    private function getStaticTasks(): array
    {
        //====================================================================//
        // Create A Generic Event
        $listingEvent = new StaticTasksListingEvent();
        //====================================================================//
        // Fetch List of Static Tasks from Parameters
        $listingEvent->setArguments($this->config->static);
        //====================================================================//
        // Complete List of Static Tasks via Event Listener
        /** @var StaticTasksListingEvent $resultEvent */
        $resultEvent = $this->dispatcher->dispatch($listingEvent);

        return $resultEvent->getArguments();
    }

    /**
     * Identify Static Task in Parameters
     *
     * @param array $staticTask
     * @param Task  $task
     *
     * @return bool true if Static Tasks are Similar
     */
    private function compareStaticTask(array $staticTask, Task $task) : bool
    {
        //====================================================================//
        // Filter by Class Name
        if ($staticTask["class"] != $task->getJobClass()) {
            return false;
        }
        //====================================================================//
        // Filter by Token
        if ($staticTask["token"] != $task->getJobToken()) {
            return false;
        }
        //====================================================================//
        // Filter by Frequency
        if ($staticTask["frequency"] != $task->getJobFrequency()) {
            return false;
        }
        //====================================================================//
        // Filter by Inputs
        if (serialize($staticTask["inputs"]) !== serialize($task->getJobInputs())) {
            return false;
        }

        return true;
    }
}
