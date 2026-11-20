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

namespace BadPixxel\Tasking\Services\Tasks;

use BadPixxel\Tasking\Services\Configuration;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Tasking Status Helper
 * Provide information for Task Management inside Workers
 */
class StatusMonitor
{
    /**
     * @var null|string
     */
    private ?string $token = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $tokenAcquiredAt = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $tokenExpireAt = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $watchdogResetAt = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $watchdogExpireAt = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $jobStartedAt = null;

    /**
     * @var null|DateTime
     */
    private ?DateTime $jobExpireAt = null;

    /**
     * Last Time Current Job was Marked as Alive in Database
     */
    private ?DateTime $jobAliveAt = null;

    /**
     * Current Task Entity ID
     */
    private ?int $taskId = null;

    /**
     * Current Worker Entity ID
     */
    private ?int $workerId = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Configuration $configuration,
    ) {
    }

    //==============================================================================
    // MAIN DELAYS MANAGEMENT
    //==============================================================================

    /**
     * Ensure at least $nbSeconds remain for running current Job.
     *
     * If possible, watchdog (PHP Time Limit) will be extended.
     *
     * @return bool True if this delay is Allowed
     */
    public function requireLifetime(int $nbSeconds): bool
    {
        $remaining = $this->getRemainingLifetime();
        //==============================================================================
        // Current Situation allow this delay
        if (is_null($remaining) || ($nbSeconds <= $remaining)) {
            return true;
        }
        $extendable = $this->getExpendableLifetime();
        //==============================================================================
        // Watchdog Reset is Still Possible
        if (!is_null($extendable) && ($this->configuration->getWorkerWatchdogDelay() < $extendable)) {
            //==============================================================================
            // Reset Watchdog
            $this->resetWatchdog();

            return true;
        }

        //==============================================================================
        // Delay not Allowed
        return false;
    }

    /**
     * Check if at least $nbSeconds remain for running current Job.
     *
     * @param int $nbSeconds
     *
     * @return bool True if this delay is Allowed
     */
    public function hasLifetime(int $nbSeconds): bool
    {
        $remaining = $this->getRemainingLifetime();
        //==============================================================================
        // Current Situation allow this delay
        if (is_null($remaining) || ($nbSeconds <= $remaining)) {
            return true;
        }

        return false;
    }

    /**
     * Get Job Delays Status
     *
     * @return array<string, null|int>
     */
    public function getStatus(): array
    {
        return array(
            "job" => $this->getJobLifetime(),
            "token" => $this->getTokenLifetime(),
            "watchdog" => $this->getWatchdogLifetime(),
            "remaining" => $this->getRemainingLifetime(),
            "expandable" => $this->getExpendableLifetime(),
        );
    }

    /**
     * Get Remaining Lifetime in Seconds
     *
     * This is the Max Time before:
     *  - PHP script may fall in timeout.
     *  - Job Token may expire
     *  - Job may be considered as faulty by scheduler
     */
    public function getRemainingLifetime(): ?int
    {
        $min = min(array(
            $this->getJobLifetime() ? $this->getJobLifetime() : PHP_INT_MAX,
            $this->getTokenLifetime() ? $this->getTokenLifetime() : PHP_INT_MAX,
            $this->getWatchdogLifetime() ? $this->getWatchdogLifetime() : PHP_INT_MAX,
        ));

        return (PHP_INT_MAX == $min) ? null : (int) $min;
    }

    /**
     * Get Expendable Lifetime.
     *
     * This delay is a technical value indicating Max Time before:
     *  - Job Token may expire
     *  - Job may be considered as faulty by scheduler
     */
    public function getExpendableLifetime(): ?int
    {
        $min = min(array(
            $this->getJobLifetime() ? $this->getJobLifetime() : PHP_INT_MAX,
            $this->getTokenLifetime() ? $this->getTokenLifetime() : PHP_INT_MAX,
        ));

        return (PHP_INT_MAX == $min) ? null : (int) $min;
    }

    /**
     * Keep Current Job Alive for at least $nbSeconds.
     *
     * Same rules as requireLifetime(): the job & token lifetimes cap the request.
     * When granted, watchdog (PHP Time Limit) is reset for the longest of the
     * requested delay and the watchdog delay, never beyond the expendable lifetime.
     *
     * @param int $nbSeconds Requested Lifetime in Seconds
     *
     * @return bool True if this delay is Allowed
     */
    public function keepAlive(int $nbSeconds): bool
    {
        //==============================================================================
        // No Running Job => Nothing to Keep Alive
        if (!$this->isJobRunning()) {
            return false;
        }
        $nbSeconds = max(1, $nbSeconds);
        $extendable = $this->getExpendableLifetime();
        //==============================================================================
        // Requested Delay Exceed Job or Token Lifetime
        if (!is_null($extendable) && ($nbSeconds > $extendable)) {
            return false;
        }
        //==============================================================================
        // Reset Watchdog
        $delay = max($nbSeconds, $this->configuration->getWorkerWatchdogDelay());
        $this->setWatchdog(is_null($extendable) ? $delay : min($delay, $extendable));
        $this->logger->debug(sprintf("Status Manager: Job kept alive for %d Seconds", $nbSeconds));

        return true;
    }

    //==============================================================================
    // JOB DELAYS MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller a job was Started
     *
     * @param null|int $taskId Started Task Entity ID
     */
    public function setJobStarted(?int $taskId = null): void
    {
        //==============================================================================
        // Store Job Time Limits
        $this->taskId = $taskId;
        $this->jobStartedAt = new DateTime();
        //==============================================================================
        // A Job is Alive when it Starts
        $this->setJobAlive();
    }

    /**
     * Notify Status controller current job was Marked as Alive in Database
     *
     * Job lifetime slides with alive marks, exactly like the replay rule of running tasks.
     */
    public function setJobAlive(): void
    {
        $this->jobAliveAt = new DateTime();

        try {
            $this->jobExpireAt = new DateTime("+".$this->configuration->getTasksErrorDelay()." Seconds");
        } catch (Exception) {
            $this->jobExpireAt = new DateTime("+250 Seconds");
        }
    }

    /**
     * Check if Current Job Alive Mark is Outdated in Database
     *
     * Database writes are throttled to one per worker refresh delay.
     */
    public function isJobAliveRefreshNeeded(): bool
    {
        if (!$this->isJobRunning()) {
            return false;
        }
        if (null === $this->jobAliveAt) {
            return true;
        }
        $refreshDelay = $this->configuration->getWorkerRefreshDelay();

        return ((new DateTime())->getTimestamp() - $this->jobAliveAt->getTimestamp()) >= $refreshDelay;
    }

    /**
     * Notify Status controller a job was Finished
     */
    public function setJobFinished(): void
    {
        //==============================================================================
        // Store Job Time Limits
        $this->taskId = null;
        $this->jobStartedAt = null;
        $this->jobExpireAt = null;
        $this->jobAliveAt = null;
    }

    /**
     * Check if a Job is Currently Running
     */
    public function isJobRunning(): bool
    {
        return null !== $this->jobStartedAt;
    }

    /**
     * Get Current Task Entity ID
     */
    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    /**
     * Get Number of Seconds before Job Expiration
     */
    public function getJobLifetime(): ?int
    {
        if ($this->jobExpireAt) {
            return $this->jobExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return null;
    }

    /**
     * @return null|DateTime
     */
    public function getJobStartedAt(): ?DateTime
    {
        return $this->jobStartedAt;
    }

    /**
     * @return null|DateTime
     */
    public function getJobExpireAt(): ?DateTime
    {
        return $this->jobExpireAt;
    }

    //==============================================================================
    // WATCHDOG MANAGEMENT
    //==============================================================================

    /**
     * Reset Worker & Tasks WatchDog
     */
    public function resetWatchdog(): void
    {
        $watchdogDelay = $this->configuration->getWorkerWatchdogDelay();
        //==============================================================================
        // Store New Process Execution Time Limit
        $this->setWatchdog($watchdogDelay);
        //==============================================================================
        // Add Log Message
        $this->logger->warning(sprintf("Status Manager: Watchdog reset for %d Seconds", $watchdogDelay));
    }

    /**
     * Get Number of Seconds before Watchdog Expiration
     */
    public function getWatchdogLifetime(): ?int
    {
        if ($this->watchdogExpireAt) {
            return $this->watchdogExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return null;
    }

    /**
     * @return null|DateTime
     */
    public function getWatchdogResetAt(): ?DateTime
    {
        return $this->watchdogResetAt;
    }

    //==============================================================================
    // WORKER MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller of Current Worker
     *
     * @param null|int $workerId Current Worker Entity ID
     */
    public function setWorkerId(?int $workerId): void
    {
        $this->workerId = $workerId;
    }

    /**
     * Get Current Worker Entity ID
     */
    public function getWorkerId(): ?int
    {
        return $this->workerId;
    }

    //==============================================================================
    // TASKS TOKEN MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller a token was Acquired
     */
    public function setTokenAcquired(string $token): void
    {
        $this->token = $token;
        $this->tokenAcquiredAt = new DateTime();

        try {
            $this->tokenExpireAt = new DateTime("+".$this->configuration->getTokenSelfReleaseDelay()." Seconds");
        } catch (Exception) {
            $this->tokenExpireAt = new DateTime("+300 Seconds");
        }
    }

    /**
     * Notify Status controller a token was Released
     */
    public function setTokenReleased(): void
    {
        $this->token = null;
        $this->tokenAcquiredAt = null;
        $this->tokenExpireAt = null;
    }

    /**
     * Check if a Token is Used
     */
    public function hasToken(): bool
    {
        return isset($this->token);
    }

    /**
     * Get Currently Used Token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get Number of Seconds before Token Expiration
     */
    public function getTokenLifetime(): ?int
    {
        if ($this->tokenExpireAt) {
            return $this->tokenExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return null;
    }

    /**
     * Does Current token Have Enough Lifetime for Another Task Execution
     */
    public function hasTokenEnoughLifetime(): bool
    {
        return $this->getTokenLifetime() >= $this->configuration->getWorkerWatchdogDelay();
    }

    /**
     * Get Date of Token Expirations
     */
    public function getTokenAcquiredAt(): ?DateTime
    {
        return $this->tokenAcquiredAt;
    }

    //==============================================================================
    // PRIVATE METHODS
    //==============================================================================

    /**
     * Set Process Execution Time Limit
     *
     * @param int $nbSeconds Delay in Seconds
     *
     * @return void
     */
    private function setWatchdog(int $nbSeconds): void
    {
        //==============================================================================
        // Store New Process Execution Time Limit
        $this->watchdogResetAt = new DateTime();

        try {
            $this->watchdogExpireAt = new DateTime("+".$nbSeconds." Seconds");
        } catch (Exception) {
            $this->watchdogExpireAt = new DateTime("+30 Seconds");
        }
        //==============================================================================
        // Set Script Execution Time
        set_time_limit($nbSeconds);
    }
}
