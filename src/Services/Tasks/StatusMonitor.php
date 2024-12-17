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

    //==============================================================================
    // JOB DELAYS MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller a job was Started
     */
    public function setJobStarted(): void
    {
        //==============================================================================
        // Store Job Time Limits
        $this->jobStartedAt = new DateTime();

        try {
            $this->jobExpireAt = new DateTime("+".$this->configuration->getTasksErrorDelay()." Seconds");
        } catch (Exception) {
            $this->jobExpireAt = new DateTime("+250 Seconds");
        }
    }

    /**
     * Notify Status controller a job was Finished
     */
    public function setJobFinished(): void
    {
        //==============================================================================
        // Store Job Time Limits
        $this->jobStartedAt = null;
        $this->jobExpireAt = null;
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
        $this->watchdogResetAt = new DateTime();

        try {
            $this->watchdogExpireAt = new DateTime("+".$watchdogDelay." Seconds");
        } catch (Exception) {
            $this->watchdogExpireAt = new DateTime("+30 Seconds");
        }
        //==============================================================================
        // Set Script Execution Time
        set_time_limit($watchdogDelay);
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
}
