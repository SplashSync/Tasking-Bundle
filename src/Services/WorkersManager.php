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
use BadPixxel\Tasking\Entity\Worker;
use BadPixxel\Tasking\Helper\Timer;
use BadPixxel\Tasking\Services\Tasks\StatusMonitor;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Workers Management Service
 */
class WorkersManager
{
    //==============================================================================
    //  Variables Definition
    //==============================================================================

    /**
     * Worker Object
     *
     * @var Worker
     */
    private Worker $worker;

    /**
     * Script Max End Date
     *
     * @var DateTime
     */
    private DateTime $endDate;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @throws Exception
     */
    public function __construct(
        private readonly ProcessManager $process,
        protected readonly StatusMonitor $statusMonitor,
        protected readonly Configuration $configuration,
        protected readonly LoggerInterface $logger,
    ) {
        //====================================================================//
        // Setup End Of Life Date
        $this->endDate = $this->getWorkerMaxDate();
    }

    //==============================================================================
    //      Worker Operations
    //==============================================================================

    /**
     * Initialize Current Worker Process
     */
    public function initialize(int $processId): void
    {
        //====================================================================//
        // Identify Current Worker by Linux Process PID
        $worker = $this->configuration->getWorkerRepository()->findOneByLinuxPid();
        //====================================================================//
        // If Worker Not Found => Search By Supervisor Process Number
        if (null === $worker) {
            //====================================================================//
            // Search Worker By Process Number
            $worker = $this->configuration->getWorkerRepository()->findOneByProcess($processId);
        }
        //====================================================================//
        // If Worker Doesn't Exists
        if (null === $worker) {
            //====================================================================//
            // Create Worker
            $worker = $this->create($processId);
        }
        //====================================================================//
        // Setup End Of Life Date
        $this->endDate = $this->getWorkerMaxDate();
        //====================================================================//
        // Update pID
        $this->worker = $worker;
        $this->worker->setPid((int) getmypid());
        $this->worker->setTask("Boot...");
        $this->configuration->getEntityManager()->flush();
        //====================================================================//
        // Refresh Worker
        $this->refresh(true);
    }

    /**
     * Refresh Status of a Supervisor Process
     *
     * @param bool $force
     *
     * @throws Exception
     *
     * @return Worker
     */
    public function refresh(bool $force) : Worker
    {
        //====================================================================//
        // Safety Check
        if (!isset($this->worker)) {
            throw new Exception("No Current Worker for refresh!!");
        }
        //====================================================================//
        // Update Status is Needed?
        if (false === $this->isRefreshNeeded($force)) {
            return $this->worker;
        }
        //====================================================================//
        // Reload Worker From DB
        $this->configuration->getEntityManager()->clear();
        $worker = $this->configuration->getWorkerRepository()->findOneByProcess($this->worker->getProcess());
        if (null === $worker) {
            throw new Exception("Unable to reload Worker from Database");
        }
        $this->worker = $worker;

        //==============================================================================
        // Refresh Worker Status
        //==============================================================================

        //==============================================================================
        // Set As Running
        $worker->setRunning(true);
        //==============================================================================
        // Set As Running
        $worker->setPid((int) getmypid());
        //==============================================================================
        // Set Last Seen DateTime to NOW
        $worker->setLastSeen(new DateTime());
        //==============================================================================
        // Set Script Execution Time
        $this->statusMonitor->resetWatchdog();
        //==============================================================================
        // Set Status as Waiting
        $worker->setTask(Timer::isIdle() ? "Working !!" : "Waiting...");
        //==============================================================================
        // Flush Database
        $this->configuration->getEntityManager()->flush();
        //====================================================================//
        // Output Refresh Sign
        $this->logger->info("Worker Manager: Worker ".$worker->getProcess()." Refreshed in Database");

        return $worker;
    }

    /**
     * Verify a Worker Process is running
     *
     * @param int $processId Worker Local Id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isRunning(int $processId): bool
    {
        //====================================================================//
        // Load Local Machine Worker
        $worker = $this->configuration->getWorkerRepository()->findOneByProcess($processId);
        //====================================================================//
        // Worker Found & Running
        if (($worker instanceof Worker) && $worker->isRunning()) {
            return true;
        }
        //====================================================================//
        // Worker Found & Running
        if (null === $worker) {
            $this->logger->info("Worker Manager: Workers Process ".$processId." doesn't Exists");

            return false;
        }
        //====================================================================//
        // Worker Is Disabled
        if (!$worker->isEnabled()) {
            $this->logger->info("Worker Manager: Workers Process ".$processId." is Disabled");

            return true;
        }
        //====================================================================//
        // Worker Is Inactive
        $this->logger->info("Worker Manager: Workers Process ".$processId." is Inactive");

        //====================================================================//
        // Worker Not Alive
        return false;
    }

    /**
     * Start a Worker Process on Local Machine (Server Node)
     *
     * @param int $processId Worker Process Id
     *
     * @return bool
     */
    public function start(int $processId) : bool
    {
        return $this->process->start(ProcessManager::WORKER." ".$processId);
    }

    /**
     * Update Current Worker to Stopped Status
     *
     * @throws Exception
     */
    public function stop() : void
    {
        //====================================================================//
        // Safety Check
        if (!isset($this->worker)) {
            throw new Exception("No Current Worker Initialized!!");
        }
        //====================================================================//
        // Refresh Supervisor Worker Status (WatchDog)
        $this->refresh(true);

        //====================================================================//
        // Set Status as Stopped
        $this->worker->setTask("Stopped");
        $this->worker->setRunning(false);
        $this->configuration->getEntityManager()->flush();

        //====================================================================//
        // Check if Worker is to Restart Automatically
        if ($this->worker->isEnabled()) {
            //====================================================================//
            // Ensure Supervisor is Running
            $this->checkSupervisor();
        }
        //====================================================================//
        // User Information
        $this->logger->info('End of Worker Process... See you later babe!');
    }

    /**
     * Update Enable Flag of All Available Workers
     *
     * @param bool $enabled
     *
     * @throws Exception
     */
    public function setupAllWorkers(bool $enabled) : void
    {
        //====================================================================//
        // Clear EntityManager
        $this->configuration->getEntityManager()->clear();
        //====================================================================//
        // Load List of All Currently Setup Workers
        $workers = $this->configuration->getWorkerRepository()->findAll();
        //====================================================================//
        // Update All Actives Workers as Disabled
        foreach ($workers as $worker) {
            //====================================================================//
            // Update Worker Status
            $worker->setEnabled($enabled);
        }
        //====================================================================//
        // Save Changes to Db
        $this->configuration->getEntityManager()->flush();
    }

    /**
     * Count Number of Active Workers
     *
     * @throws Exception
     *
     * @return int
     */
    public function countActiveWorkers() : int
    {
        return $this->configuration->getWorkerRepository()->countActiveWorkers();
    }

    /**
     * Check if Worker Needs To Be Restarted
     *
     * @param null|int $taskCount Number of Tasks Already Executed
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isToKill(?int $taskCount): bool
    {
        //====================================================================//
        // Safety Check
        if (!isset($this->worker)) {
            throw new Exception("No Current Worker Initialized!!");
        }
        //====================================================================//
        // Check Tasks Counter
        if (!is_null($taskCount) && ($taskCount >= $this->configuration->getWorkerMaxTasks())) {
            $this->logger->info("Worker Manager: Exit on Worker Tasks Counter (".$taskCount.")");

            return true;
        }
        //====================================================================//
        // Check Worker Age
        if ($this->endDate < (new DateTime())) {
            $this->logger->info("Worker Manager: Exit on Worker TimeOut");

            return true;
        }
        //====================================================================//
        // Check Worker Memory Usage
        if ((memory_get_usage(true) / 1048576) > $this->getWorkerMaxMemory()) {
            $this->logger->info("Worker Manager: Exit on Worker Memory Usage");

            return true;
        }
        //====================================================================//
        // Check User requested Worker to Stop
        if (false === $this->worker->isEnabled()) {
            $this->logger->info("Worker Manager: Exit on User Request, Worker Now Disabled");

            return true;
        }
        //====================================================================//
        // Check Worker is Not Alone with this Number
        if ($this->process->exists($this->getWorkerCommandName()) > 1) {
            $this->logger->warning("Worker Manager: Exit on Duplicate Worker Deleted");

            return true;
        }

        return false;
    }

    /**
     * Set Current Worker Task
     *
     * @param Task $task Current Worker Task
     *
     * @throws Exception
     */
    public function setCurrentTask(Task $task) : void
    {
        //====================================================================//
        // Safety Check
        if (!isset($this->worker)) {
            throw new Exception("No Current Worker Initialized!!");
        }
        $this->worker->setTask($task->getName());
    }

    //==============================================================================
    //      Supervisor Verifications
    //==============================================================================

    /**
     * Check All Available Supervisor are Running on All machines
     *
     * @throws Exception
     *
     * @return bool
     */
    public function checkSupervisor(): bool
    {
        //====================================================================//
        // Check Local Machine Crontab
        $this->process->checkCrontab();
        //====================================================================//
        // Check Local Machine Supervisor
        $result = $this->checkLocalSupervisorIsRunning();
        //====================================================================//
        // Check if MultiServer Mode is Enabled
        if (true !== $this->configuration->isMultiServer()) {
            return $result;
        }
        //====================================================================//
        // Retrieve List of All Supervisors
        /** @var Worker[] $workers */
        $workers = $this->configuration->getWorkerRepository()->findBy(array("process" => 0));
        //====================================================================//
        // Check All Supervisors
        foreach ($workers as $supervisor) {
            $result = $result && $this->checkRemoteSupervisorsAreRunning($supervisor);
        }

        return $result;
    }

    //==============================================================================
    //      PROTECTED - Worker Config Informations
    //==============================================================================

    /**
     * Get Worker Command Type Name
     *
     * @return string
     */
    protected function getWorkerCommandName(): string
    {
        return ProcessManager::WORKER." ".$this->worker->getProcess();
    }

    /**
     * Get Max Age for Worker (since now)
     *
     * @return DateTime
     */
    protected function getWorkerMaxDate(): DateTime
    {
        $this->logger->info(
            "Worker Manager: This Worker will die in ".$this->configuration->getWorkerMaxAge()." Seconds"
        );

        try {
            return new DateTime("+".$this->configuration->getWorkerMaxAge()." Seconds");
        } catch (Exception) {
            return new DateTime("+120 Seconds");
        }
    }

    /**
     * Get Max Memory Usage for Worker (in Mb)
     *
     * @return int
     */
    protected function getWorkerMaxMemory(): int
    {
        return $this->configuration->getWorkerMaxMemory();
    }

    /**
     * Check if Worker Status is to Refresh
     *
     * @param bool $force
     *
     * @throws Exception
     *
     * @return bool
     */
    private function isRefreshNeeded(bool $force) : bool
    {
        //====================================================================//
        // Forced Refresh
        if ($force) {
            return true;
        }
        //====================================================================//
        // Compute Refresh Limit
        $refreshLimit = new DateTime("-".$this->configuration->getWorkerRefreshDelay()." Seconds");
        $lastSeen = $this->worker->getLastSeen();

        //====================================================================//
        // LastSeen Outdated => Refresh Needed
        return ($lastSeen->getTimestamp() < $refreshLimit->getTimestamp());
    }

    //==============================================================================
    //      PROTECTED - Worker CRUD
    //==============================================================================

    /**
     * Create a new Worker Object for this Process
     *
     * @param int $processId Worker Process Id
     *
     * @return Worker
     */
    private function create(int $processId = 0): Worker
    {
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //====================================================================//
        // Create Worker Object
        $worker = new Worker();
        //====================================================================//
        // Populate Worker Object
        /** @var scalar $serverIp */
        $serverIp = filter_input(INPUT_SERVER, "SERVER_ADDR");
        $worker->setPid((int) getmypid());
        $worker->setProcess($processId);
        $worker->setNodeName(is_array($system) ? $system["nodename"] : "Unknown");
        $worker->setNodeIp((string) $serverIp);
        $worker->setNodeInfos(is_array($system) ? $system["version"] :  "0.0.0");
        $worker->setLastSeen(new DateTime());
        //====================================================================//
        // Persist Worker Object to Database
        $this->configuration->getEntityManager()->persist($worker);
        $this->configuration->getEntityManager()->flush();

        return $worker;
    }

    //==============================================================================
    //      PRIVATE - Supervisor Verifications
    //==============================================================================

    /**
     * Check Supervisor is Running on this machine
     * ==> Start a Supervisor Process if needed
     *
     * @throws Exception
     *
     * @return bool
     */
    private function checkLocalSupervisorIsRunning() : bool
    {
        //====================================================================//
        // Load Local Machine Supervisor
        $supervisor = $this->configuration->getWorkerRepository()->findOneByProcess(0);
        //====================================================================//
        // Supervisor Exists
        if ($supervisor instanceof Worker) {
            //====================================================================//
            // Supervisor Is Running
            if (!$supervisor->isEnabled() || $supervisor->isRunning()) {
                //====================================================================//
                // YES =>    Exit
                $this->logger->info("Worker Manager: Local Supervisor is Running or Disabled");

                return true;
            }
        }
        //====================================================================//
        // NO =>    Start Supervisor Process
        $this->logger->notice("Worker Manager: Local Supervisor not Running");

        return $this->process->start(ProcessManager::SUPERVISOR);
    }

    /**
     * Check All Available Supervisor are Running on All machines
     *
     * @param Worker $supervisor
     *
     * @return bool
     */
    private function checkRemoteSupervisorsAreRunning(Worker $supervisor): bool
    {
        //====================================================================//
        // Refresh From DataBase
        $this->configuration->getEntityManager()->refresh($supervisor);
        //====================================================================//
        // If Supervisor Is NOT Running
        if ($supervisor->isRunning() || !$this->configuration->isMultiServer()) {
            return true;
        }
        //====================================================================//
        // Send REST Request to Start
        $url = "http://".$supervisor->getNodeIp().$this->configuration->getMultiServerPath();
        //====================================================================//
        // Send REST Request to Start
        $request = curl_init($url);
        if (false === $request) {
            return false;
        }
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HEADER, false);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 1);
        curl_exec($request);
        curl_close($request);

        return true;
    }
}
