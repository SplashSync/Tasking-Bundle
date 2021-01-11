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
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Events\AddEvent;
use Splash\Tasking\Events\CheckEvent;
use Splash\Tasking\Events\InsertEvent;
use Splash\Tasking\Events\StaticTasksListingEvent;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractStaticJob;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\TokenRepository;
use Splash\Tasking\Repository\WorkerRepository;
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
    //==============================================================================
    //  Variables Definition
    //==============================================================================

    /**
     * Doctrine Entity Manager
     *
     * @var ObjectManager
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

    /**
     * @var TasksManager
     */
    private static $staticInstance;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @param array                    $config
     * @param Registry                 $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param TokenManager             $token
     *
     * @throws Exception
     */
    public function __construct(
        array $config,
        Registry $doctrine,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        TokenManager $token
    ) {
        //====================================================================//
        // Link to entity manager Service
        $this->entityManager = $doctrine->getManager($config["entity_manager"]);
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
        //====================================================================//
        // Link to Symfony Event Dispatcher
        $this->dispatcher = $dispatcher;
        //====================================================================//
        // Link to Tasks Repository
        $taskRepository = $this->entityManager->getRepository(Task::class);
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
        //==============================================================================
        // Store Static Instance for Access as Static
        static::$staticInstance = $this;
    }

    //====================================================================//
    //  SubServices Access
    //====================================================================//

    /**
     * Get Tasking Entity Manager
     *
     * @return ObjectManager
     */
    public function getManager(): ObjectManager
    {
        return $this->entityManager;
    }

    /**
     * Get Tasks Repository
     *
     * @throws Exception
     *
     * @return TaskRepository
     */
    public function getTasksRepository(): TaskRepository
    {
        $repository = $this->entityManager->getRepository(Task::class);

        if (!($repository instanceof TaskRepository)) {
            throw new Exception("Unable to Load Tasks Repository");
        }

        return $repository;
    }

    /**
     * Get Worker Repository
     *
     * @throws Exception
     *
     * @return WorkerRepository
     */
    public function getWorkerRepository(): WorkerRepository
    {
        $repository = $this->entityManager->getRepository(Worker::class);

        if (!($repository instanceof WorkerRepository)) {
            throw new Exception("Unable to Load Worker Repository");
        }

        return $repository;
    }

    /**
     * Get Token Repository
     *
     * @throws Exception
     *
     * @return TokenRepository
     */
    public function getTokenRepository(): TokenRepository
    {
        $repository = $this->entityManager->getRepository(Token::class);

        if (!($repository instanceof TokenRepository)) {
            throw new Exception("Unable to Load Token Repository");
        }

        return $repository;
    }

    //====================================================================//
    //  Generic Tasks Management
    //====================================================================//

    /**
     * Start Tasking Supervisor on This Machine
     *
     * @throws ReflectionException
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
     * @throws NonUniqueResultException
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
        // Reload Repository Data
        $this->entityManager->clear();

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
     * @throws NonUniqueResultException
     * @throws NoResultException
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

                $this->onAddAction(new GenericEvent($job));
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
     * @param GenericEvent $event
     *
     * @return bool
     */
    public function onAddAction(GenericEvent $event): bool
    {
        $job = $event->getSubject();
        //====================================================================//
        // Validate Job
        if (!($job instanceof AbstractJob) || !$this->validate($job)) {
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
     * Insert Tasks in DataBase
     *
     * @param Task $task Task Item to Insert
     */
    private function insert(Task $task): void
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
        $resultEvent = self::dispatch($listingEvent);

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

    /**
     * Dispatch an Event with Args Detection
     *
     * @param GenericEvent $event
     *
     * @return null|AddEvent|CheckEvent|InsertEvent
     */
    private static function dispatch(GenericEvent $event): ?GenericEvent
    {
        try {
            $reflection = new ReflectionMethod(static::$staticInstance->dispatcher, "dispatch");
            $args = array();
            foreach ($reflection->getParameters() as $param) {
                if ("event" == $param->getName()) {
                    $args[] = $event;
                }
                if ("eventName" == $param->getName()) {
                    $args[] = get_class($event);
                }
            }
        } catch (ReflectionException $ex) {
            return null;
        }

        return $reflection->invokeArgs(static::$staticInstance->dispatcher, $args);
    }
}
