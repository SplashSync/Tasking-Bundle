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

namespace Splash\Tasking\Services;

use ArrayObject;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Tools\Timer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tasks Runner
 *
 * Load Available Tasks from database, Acquire Token & Execute
 * Look so simple... but!
 */
class Runner
{
    /**
     * Symfony Service Container
     * Used for On-Demand Injection in Task
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Tasks Repository
     *
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * Token Manager Service
     *
     * @var TokenManager
     */
    private $token;

    /**
     * Tasking Service Configuration Array
     *
     * @var ArrayObject
     */
    private $config;

    /**
     * Tasks Max Try Count
     *
     * @var int
     */
    private $maxTry = 10;

    /**
     * Current Task Class to Execute
     *
     * @var null|Task
     */
    private $task;

    /**
     * @var AbstractJob
     */
    private $job;

    /**
     * Service Constructor
     *
     * @param ContainerInterface $container
     * @param LoggerInterface    $logger
     * @param TaskRepository     $tasks
     * @param TokenManager       $token
     * @param array              $config
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, TaskRepository $tasks, TokenManager $token, array $config)
    {
        //====================================================================//
        // Link to Service Container
        $this->container = $container;
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
        //====================================================================//
        // Link to Tasks Repository
        $this->taskRepository = $tasks;
        //====================================================================//
        // Link to Token Manager
        $this->token = $token;
        //====================================================================//
        // Init Parameters
        $this->config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS) ;
        $this->maxTry = $config['tasks']["try_count"];
    }

    //==============================================================================
    //      PUBLIC - Task Execution Management
    //==============================================================================

    /**
     * Ask Runner to Execute Next Available Task
     *
     * @return bool True if A Task was Executed
     */
    public function run(): bool
    {
        //====================================================================//
        // Store Task Startup Time
        Timer::start();
        //====================================================================//
        // Run Next Normal Tasks
        if ($this->runNextTask(false)) {
            //====================================================================//
            // A Task was Executed
            Timer::wipStandBy();

            return true;
        }
        //====================================================================//
        // Run Next Static Tasks
        if ($this->runNextTask(true)) {
            //====================================================================//
            // A Task was Executed
            Timer::wipStandBy();

            return true;
        }
        //====================================================================//
        // Wait
        Timer::idleStandBy();

        return false;
    }

    /**
     * Ensure We released The Current Tokan
     *
     * @return bool
     */
    public function ensureTokenRelease(): bool
    {
        return $this->token->release();
    }


    //==============================================================================
    //      PRIVATE - Task Execution Management
    //==============================================================================

    /**
     * Execute Next Available Task
     *
     * @param bool $staticMode Execute Static Tasks
     *
     * @return boolean
     */
    private function runNextTask(bool $staticMode) : bool
    {
        //====================================================================//
        // Load Next Task To Run with Current Token
        $this->loadNextTask($staticMode);
        //====================================================================//
        // No Tasks To Execute
        if (is_null($this->task)) {
            //====================================================================//
            // Release Token (Return True only if An Active Token was released)
            return $this->token->release();
        }

        //====================================================================//
        // Acquire or Verify Token For this Task
        if (!$this->token->acquire($this->task)) {
            //====================================================================//
            // Token Acquire Refused
            //  => This Should Never Happen
            //  => If Rejected Process will Die
            return false;
        }

        //==============================================================================
        // Validate & Prepare User Job Execution
        //==============================================================================
        if (!$this->validateJob($this->task) || !$this->prepareJob($this->task)) {
            $this->task->setTry($this->task->getTry() + 1);
            //==============================================================================
            // Save Status in Db
            $this->taskRepository->flush($this->task);

            return true;
        }

        //==============================================================================
        // Save Status in Db
        $this->taskRepository->flush($this->task);
        
        //====================================================================//
        // Exectue Task
        //====================================================================//
        $this->executeJob($this->task);

        //==============================================================================
        // Do Post Execution Actions
        $this->closeJob($this->task, $this->maxTry);

        //==============================================================================
        // Save Status in Db
        $this->taskRepository->flush($this->task);

        //====================================================================//
        // Exit & Ask for a Next Round
        return true;
    }

    /**
     * Load Next Available Tasks with Potential Existing Token
     *
     * @param bool $staticMode Execute Static Tasks
     */
    private function loadNextTask(bool $staticMode) : void
    {
        //====================================================================//
        // Use Current Task Token or Null
        $currentToken = isset($this->task)
                ? $this->task->getJobToken()
                : null;

        //====================================================================//
        // Load Next Task To Run with Current Token
        $this->task = $this->taskRepository->getNextTask(
            $this->config->tasks,
            $currentToken,
            $staticMode
        );
    }

    //==============================================================================
    //      PRIVATE - Job Execution Management
    //==============================================================================

    /**
     * Validate Job Before Execution
     *
     * @param Task $task
     *
     * @return bool
     */
    private function validateJob(Task &$task): bool
    {
        //==============================================================================
        // Load Requested Class
        $jobClass = $task->getJobClass();
        if (!class_exists($jobClass)) {
            $task->setFaultStr("Unable to find Requested Job Class : ".$jobClass);

            return false;
        }
        $this->job = new $jobClass();
        //====================================================================//
        // Job Class is SubClass of Base Job Class
        if (!is_subclass_of($this->job, AbstractJob::class)) {
            $task->setFaultStr("Job Class is Invalid: ".$jobClass);

            return false;
        }
        //====================================================================//
        // Inject Container to Job Class
        $this->job->setContainer($this->container);
        //==============================================================================
        // Verify Requested Method Exists
        $jobAction = $task->getJobAction();
        if (!method_exists($this->job, $jobAction)) {
            $task->setFaultStr("Unable to find Requested Job Function");

            return false;
        }

        return true;
    }

    /**
     * Prepare Job For Execution
     *
     * @param Task $task
     *
     * @return bool
     */
    private function prepareJob(Task &$task): bool
    {
        //====================================================================//
        // Init Task
        $task->setRunning(true);
        $task->setFinished(false);
        $task->setStartedAt();
        $task->setStartedBy($task->getCurrentServer());
        $task->setTry($task->getTry() + 1);
        $task->setFaultStr(null);
        //====================================================================//
        // Safety Check
        if ($task->isFinished() && !$task->isStaticJob()) {
            $task->setFaultStr("Your try to Start an Already Finished Task!!");

            return false;
        }
        //====================================================================//
        // Init User Job
        $this->job->__set("inputs", $task->getJobInputs());

        //====================================================================//
        // User Information
        $this->logger->info('Execute : '.$task->getJobClass()." -> ".$task->getJobAction().'  ('.$task->getId().')');
        $this->logger->info('Parameters : '.print_r($task->getJobInputs(), true));

        return true;
    }

    /**
     * Main Function for Job Execution
     *
     * @param Task $task
     *
     * @return bool
     */
    private function executeJob(Task &$task): bool
    {
        //==============================================================================
        // Turn On Output Buffering to Get Task Outputs Captured
        ob_start();
        //==============================================================================
        // Execute Requested Operation
        //==============================================================================
        try {
            $result = $this->executeJobAction($task);
        }
        //==============================================================================
        // Catch Any Exceptions that may occur during task execution
        catch (Exception $e) {
            $result = false;
            $task->setFaultStr($e->getMessage().PHP_EOL.$e->getFile()." Line ".$e->getLine());
            $task->setFaultTrace($e->getTraceAsString());
        }
        //==============================================================================
        // Flush Output Buffer
        $task->appendOutputs((string) ob_get_contents());
        ob_end_clean();
        //==============================================================================
        // If Job is Successful => Store Status
        if ($result) {
            $task->setFinished(true);
        }

        return $result;
    }

    /**
     * Main Function for Job Execution
     *
     * @return bool
     */
    private function executeJobAction(Task &$task): bool
    {
        //==============================================================================
        // Execute Job Self Validate & Prepare Methods
        if (!$this->job->validate() || !$this->job->prepare()) {
            if (null == $task->getFaultStr()) {
                $task->setFaultStr("Unable to initiate this Job.");
            }

            return false;
        }
        //==============================================================================
        // Execute Job Action
        $result = (bool) $this->job->{$task->getJobAction()}();
        if ((false === $result) && (null == $task->getFaultStr())) {
            $task->setFaultStr("An error occured when executing this Job.");
        }
        //==============================================================================
        // Execute Job Self Finalize & Close Methods
        if (!$this->job->finalize() || !$this->job->close()) {
            if (null == $task->getFaultStr()) {
                $task->setFaultStr("An error occured when closing this Job.");
            }
        }

        return $result;
    }

    /**
     * End Task on Scheduler
     *
     * @param Task $task
     * @param int  $maxTry Max number of retry. Once reached, task is forced to finished.
     */
    private function closeJob(Task &$task, int $maxTry): void
    {
        //==============================================================================
        // End of Task Execution
        $task->setRunning(false);
        $task->setFinishedAt();
        //==============================================================================
        // If Static Task => Set Next Planned Execution Date
        if ($task->isStaticJob()) {
            $task->setTry(0);
            $task->setPlannedAt(
                new DateTime("+".$task->getJobFrequency()."Minutes ")
            );
        }
        //==============================================================================
        // Failled More than maxTry => Set Task as Finished
        if ($task->getTry() > $maxTry) {
            $task->setFinished(true);

            return;
        }
        //==============================================================================
        // IF Batch Job
        if (isset($this->job) && is_a($this->job, AbstractBatchJob::class)) {
            //==============================================================================
            // If Batch Task Not Completed => Setup For Next Execution
            if (false == $this->job->getStateItem("isCompleted")) {
                $task->setTry(0);
                $task->setFinished(false);
            }
            //==============================================================================
            // Backup Inputs Parameters For Next Actions
            $task->setJobInputs($this->job->__get("inputs"));
        }
        //====================================================================//
        // User Information
        $this->logger->info('Runner: Task Delay = '.$task->getDuration()." Milliseconds </info>");
    }    
}
