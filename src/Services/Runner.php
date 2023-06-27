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

use DateTime;
use Doctrine\Persistence\ManagerRegistry as Registry;
use Exception;
use Psr\Log\LoggerInterface;
use Sentry;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Handler\TaskHandler;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractMassJob;
use Splash\Tasking\Tools\Status;
use Splash\Tasking\Tools\Timer;
use Symfony\Bridge\Monolog\Logger;

/**
 * Tasks Runner
 *
 * Load Available Tasks from database, Acquire Token & Execute
 * Look so simple... but!
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Runner
{
    /**
     * @var TaskHandler
     */
    protected TaskHandler $taskHandler;

    /**
     * Current Task Class to Execute
     *
     * @var null|Task
     */
    private ?Task $task = null;

    /**
     * @var AbstractJob
     */
    private AbstractJob $job;

    /**
     * Service Constructor
     */
    public function __construct(
        private JobsManager $jobs,
        private LoggerInterface $logger,
        private Registry $registry,
        private TokenManager $token
    ) {
        //====================================================================//
        // Setup Tasks Logger
        $this->taskHandler = new TaskHandler();
        if ($this->logger instanceof Logger) {
            $this->logger->pushHandler($this->taskHandler);
        }
    }

    //==============================================================================
    //      PUBLIC - Task Execution Management
    //==============================================================================

    /**
     * Ask Runner to Execute Next Available Task
     *
     * @throws Exception
     *
     * @return bool True if A Task was Executed
     */
    public function run(): bool
    {
        //====================================================================//
        // Store Task Startup Time
        Timer::start();
        //==============================================================================
        // Clear Current Entity Manager
        Configuration::getTasksRepository()->clear();
        //==============================================================================
        // Clear Global Entity Manager
        $this->registry->getManager()->clear();
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
     * Ensure We released The Current Token
     *
     * @throws Exception
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
     * @throws Exception
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
            Configuration::getTasksRepository()->flush();

            return true;
        }

        //==============================================================================
        // Save Status in Db
        Configuration::getTasksRepository()->flush();

        //====================================================================//
        // Execute Task
        //====================================================================//
        $this->executeJob($this->task);

        if (isset($this->task)) {
            //==============================================================================
            // Do Post Execution Actions
            $this->closeJob($this->task, Configuration::getTasksMaxRetry());
            $this->clearEntityManagers();
            //==============================================================================
            // Save Status in Db
            Configuration::getTasksRepository()->flush();
        }

        //====================================================================//
        // Exit & Ask for a Next Round
        return true;
    }

    /**
     * Load Next Available Tasks with Potential Existing Token
     *
     * @param bool $staticMode Execute Static Tasks
     *
     * @throws Exception
     */
    private function loadNextTask(bool $staticMode) : void
    {
        //====================================================================//
        // Use Current Task Token or Null
        $currentToken = isset($this->task) && (Status::getTokenLifetime() >= Configuration::getWorkerWatchdogDelay())
                ? $this->task->getJobToken()
                : null
        ;
        //====================================================================//
        // Load Next Task To Run with Current Token
        $this->task = Configuration::getTasksRepository()->getNextTask(
            Configuration::getTasksConfiguration(),
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
    private function validateJob(Task $task): bool
    {
        //==============================================================================
        // Load Requested Job Service
        try {
            $this->job = $this->jobs->get($task->getJobClass());
        } catch (Exception $e) {
            $task->setFaultStr($e->getMessage());

            return false;
        }
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
     * @throws Exception
     *
     * @return bool
     */
    private function prepareJob(Task $task): bool
    {
        //====================================================================//
        // Init Task
        $task->setRunning(true);
        $task->setFinished(false);
        $task->setStartedAt();
        $task->setStartedBy($task->getCurrentServer());
        $task->setTry($task->getTry() + 1);
        $task->clearOutputs();
        $task->setFaultStr(null);
        //====================================================================//
        // Reset Tasks Log Handler
        $this->taskHandler->reset();
        //====================================================================//
        // Safety Check
        if ($task->isFinished() && !$task->isStaticJob()) {
            $task->setFaultStr("Your try to Start an Already Finished Task!!");

            return false;
        }
        //====================================================================//
        // Init Task Status Manager
        Status::setJobStarted();
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
     * @return void
     */
    private function executeJob(Task $task): void
    {
        //==============================================================================
        // Turn On Output Buffering to Get Task Outputs Captured
        ob_start();
        //==============================================================================
        // Execute Requested Operation
        //==============================================================================
        try {
            $result = $this->executeJobAction($task);
        } catch (Exception $exception) {
            //==============================================================================
            // Catch Any Exceptions that may occur during task execution
            $result = false;
            $task->setFaultStr($exception->getMessage().PHP_EOL.$exception->getFile()." Line ".$exception->getLine());
            $task->setFaultTrace($exception->getTraceAsString());
            //==============================================================================
            // Push Exception to Sentry if Installed
            if (function_exists('Sentry\captureException')) {
                Sentry\captureException($exception);
            }
            //====================================================================//
            // User Information
            $this->logger->error('Runner: Task Fail: '.$exception->getMessage());
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
    }

    /**
     * Main Function for Job Execution
     *
     * @param Task $task
     *
     * @return bool
     */
    private function executeJobAction(Task $task): bool
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
            $task->setFaultStr("An error occurred when executing this Job.");
        }
        //==============================================================================
        // Execute Job Self Finalize & Close Methods
        if (!$this->job->finalize() || !$this->job->close()) {
            if (null == $task->getFaultStr()) {
                $task->setFaultStr("An error occurred when closing this Job.");
            }
        }

        return $result;
    }

    /**
     * End Task on Scheduler
     *
     * @param Task $task
     * @param int  $maxTry Max number of retry. Once reached, task is forced to finished.
     *
     * @throws Exception
     */
    private function closeJob(Task $task, int $maxTry): void
    {
        //====================================================================//
        // Init Task Status Manager
        Status::setJobFinished();
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
        // Failed More than maxTry => Set Task as Finished
        if ($task->getTry() > $maxTry) {
            $task->setFinished(true);

            return;
        }
        //==============================================================================
        // IF Batch Job or Mass Job
        $job = $this->getBatchOrMassJob();
        if ($job) {
            //==============================================================================
            // If Batch Task Not Completed => Setup For Next Execution
            if (!$job->getStateItem("isCompleted")) {
                $task->setTry(0);
                $task->setFinished(false);
            }
            //==============================================================================
            // Backup Inputs Parameters For Next Actions
            $task->setJobInputs($job->getRawInputs());
        }
        //====================================================================//
        // User Information
        $this->logger->info('Runner: Task Delay = '.$task->getDuration()." Milliseconds </info>");
        //====================================================================//
        // Import Tasks Log Handler
        $task->appendOutputs($this->taskHandler->getLogAsString());
        $this->taskHandler->reset();
    }

    /**
     * Clear All Entity Managers, Except Tasking Manager
     *
     * @return void
     */
    private function clearEntityManagers(): void
    {
        foreach (array_keys($this->registry->getManagerNames()) as $managerName) {
            if ($managerName != Configuration::getEntityManagerName()) {
                $this->registry->getManager($managerName)->clear();
            }
        }
    }

    /**
     * Check if Batch or Mass Job
     *
     * @return null|AbstractBatchJob|AbstractMassJob
     */
    private function getBatchOrMassJob(): ?AbstractJob
    {
        if (!isset($this->job)) {
            return null;
        }

        if (is_a($this->job, AbstractBatchJob::class) || is_a($this->job, AbstractMassJob::class)) {
            return $this->job;
        }

        return null;
    }
}
