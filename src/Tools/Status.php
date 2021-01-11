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

namespace Splash\Tasking\Tools;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Splash\Tasking\Services\Configuration;

/**
 * Tasking Status Helper
 * Provide information for Task Management inside Workers
 */
class Status
{
    /**
     * @var null|string
     */
    private static $token;

    /**
     * @var null|DateTime
     */
    private static $tokenAcquiredAt;

    /**
     * @var null|DateTime
     */
    private static $tokenExpireAt;

    /**
     * @var null|DateTime
     */
    private static $watchdogResetAt;

    /**
     * @var null|DateTime
     */
    private static $watchdogExpireAt;

    /**
     * @var null|DateTime
     */
    private static $jobStartedAt;

    /**
     * @var null|DateTime
     */
    private static $jobExpireAt;

    /**
     * @var null|LoggerInterface
     */
    private static $logger;

    //==============================================================================
    // MAIN DELAYS MANAGEMENT
    //==============================================================================

    /**
     * Ensure at least $nbSeconds remain for running current Job.
     *
     * If possible, watchdog (PHP Time Limit) will be extended.
     *
     * @param int $nbSeconds
     *
     * @throws Exception
     *
     * @return bool True if this delay is Allowed
     */
    public static function requireLifetime(int $nbSeconds): bool
    {
        $remaining = self::getRemainingLifetime();
        //==============================================================================
        // Current Situation allow this delay
        if (is_null($remaining) || ($nbSeconds <= $remaining)) {
            return true;
        }
        $extendable = self::getExpendableLifetime();
        //==============================================================================
        // Watchdog Reset is Still Possible
        if (!is_null($extendable) && (Configuration::getWorkerWatchdogDelay() < $extendable)) {
            //==============================================================================
            // Reset Watchdog
            self::resetWatchdog();

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
     * @throws Exception
     *
     * @return bool True if this delay is Allowed
     */
    public static function hasLifetime(int $nbSeconds): bool
    {
        $remaining = self::getRemainingLifetime();
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
     * @throws Exception
     *
     * @return array
     */
    public static function getStatus(): array
    {
        return array(
            "job" => self::getJobLifetime(),
            "token" => self::getTokenLifetime(),
            "watchdog" => self::getWatchdogLifetime(),
            "remaining" => self::getRemainingLifetime(),
            "expandable" => self::getExpendableLifetime(),
        );
    }

    /**
     * Get Remaining Lifetime in Seconds
     *
     * This is the Max Time before:
     *  - PHP script may fall in timeout.
     *  - Job Token may expire
     *  - Job may be considered as faulty by scheduler
     *
     * @throws Exception
     *
     * @return int
     */
    public static function getRemainingLifetime(): ?int
    {
        $min = min(array(
            self::getJobLifetime() ? self::getJobLifetime() : PHP_INT_MAX,
            self::getTokenLifetime() ? self::getTokenLifetime() : PHP_INT_MAX,
            self::getWatchdogLifetime() ? self::getWatchdogLifetime() : PHP_INT_MAX,
        ));

        return (PHP_INT_MAX == $min) ? null : (int) $min;
    }

    /**
     * Get Expendable Lifetime.
     *
     * This delay is a technical value indicating Max Time before:
     *  - Job Token may expire
     *  - Job may be considered as faulty by scheduler
     *
     * @throws Exception
     *
     * @return int
     */
    public static function getExpendableLifetime(): ?int
    {
        $min = min(array(
            self::getJobLifetime() ? self::getJobLifetime() : PHP_INT_MAX,
            self::getTokenLifetime() ? self::getTokenLifetime() : PHP_INT_MAX,
        ));

        return (PHP_INT_MAX == $min) ? null : (int) $min;
    }

    //==============================================================================
    // JOB DELAYS MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller a job was Started
     *
     * @throws Exception
     */
    public static function setJobStarted(): void
    {
        //==============================================================================
        // Store Job Time Limits
        self::$jobStartedAt = new DateTime();
        self::$jobExpireAt = new DateTime("+".Configuration::getTasksErrorDelay()." Seconds");
    }

    /**
     * Notify Status controller a job was Finished
     *
     * @throws Exception
     */
    public static function setJobFinished(): void
    {
        //==============================================================================
        // Store Job Time Limits
        self::$jobStartedAt = null;
        self::$jobExpireAt = null;
    }

    /**
     * Get Number of Seconds before Job Expiration
     */
    public static function getJobLifetime(): ?int
    {
        if (!self::$jobExpireAt instanceof DateTime) {
            return null;
        }

        return self::$jobExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
    }

    //==============================================================================
    // WATCHDOG MANAGEMENT
    //==============================================================================

    /**
     * Reset Worker & Tasks WatchDog
     *
     * @param null|LoggerInterface $logger
     *
     * @throws Exception
     */
    public static function resetWatchdog(LoggerInterface $logger = null): void
    {
        $watchdogDelay = Configuration::getWorkerWatchdogDelay();
        //==============================================================================
        // Store New Process Execution Time Limit
        self::$watchdogResetAt = new DateTime();
        self::$watchdogExpireAt = new DateTime("+".$watchdogDelay." Seconds");
        //==============================================================================
        // Set Script Execution Time
        set_time_limit($watchdogDelay);
        //==============================================================================
        // Connect Logger
        if ($logger && !isset(self::$logger)) {
            self::$logger = $logger;
        }
        //==============================================================================
        // Add Log Message
        if (self::$logger) {
            self::$logger->warning("Status Manager: Watchdog reset for ".$watchdogDelay." Seconds");
        }
    }

    /**
     * Get Number of Seconds before Watchdog Expiration
     */
    public static function getWatchdogLifetime(): ?int
    {
        if (!self::$watchdogExpireAt instanceof DateTime) {
            return null;
        }

        return self::$watchdogExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
    }

    //==============================================================================
    // TASKS TOKEN MANAGEMENT
    //==============================================================================

    /**
     * Notify Status controller a token was Acquired
     *
     * @param string $token
     *
     * @throws Exception
     */
    public static function setTokenAcquired(string $token): void
    {
        self::$token = $token;
        self::$tokenAcquiredAt = new DateTime();
        self::$tokenExpireAt = new DateTime("+".Configuration::getTokenSelfReleaseDelay()." Seconds");
    }

    /**
     * Notify Status controller a token was Released
     */
    public static function setTokenReleased(): void
    {
        self::$token = null;
        self::$tokenAcquiredAt = null;
        self::$tokenExpireAt = null;
    }

    /**
     * Check if a Token is Used
     */
    public static function hasToken(): bool
    {
        return isset(self::$token);
    }

    /**
     * Get Currently Used Token
     */
    public static function getToken(): ?string
    {
        return self::$token;
    }

    /**
     * Get Number of Seconds before Token Expiration
     */
    public static function getTokenLifetime(): ?int
    {
        if (!self::$tokenExpireAt instanceof DateTime) {
            return null;
        }

        return self::$tokenExpireAt->getTimestamp() - (new DateTime())->getTimestamp();
    }
}
