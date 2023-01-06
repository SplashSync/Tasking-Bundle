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

namespace Splash\Tasking\Tools;

/**
 * Description of Timer
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
class Timer
{
    /**
     * Pause Delay When Inactive in Milliseconds
     *
     * => Default = 50Ms
     *
     * @var int
     */
    const STANDBY_MIN = 50;

    /**
     * First Step for Stand by Increase
     * => Default = 500Ms
     *
     * @var int
     */
    const STANDBY_IDLE = 500;

    /**
     * Second & Max Step for Stand by Increase
     * => Default = 1000Ms
     *
     * @var float
     */
    const STANDBY_MAX = 1E3;

    /**
     * Pause Delay When Inactive in Miliseconds
     *
     * => Default = 50Ms
     *
     * @var int
     */
    private static int $standBy = 0;

    /**
     * MicroTime when Last Task started
     *
     * @var float
     */
    private static float $startedAt = 0.0;

    /**
     * Execute a Millisecond Pause
     *
     * @param int $msDelay
     */
    public static function msSleep(int $msDelay): void
    {
        usleep((int) ($msDelay * 1E3));
    }

    /**
     * This Tempo Function is Called when Worker loop was completed without Job Execution.
     *
     * Each Time We Increase Wait Period Between Two Loops
     *   => On first Loops   => Minimum Pause
     *   => On next Loops    => Pause is increased until a 1 Second
     *   => Not to overload Proc & SQL Server for nothing!
     *   => When a task is executed, StandByUs is cleared
     */
    public static function idleStandBy(): void
    {
        //====================================================================//
        // Do The Pause
        self::msSleep(self::$standBy);
        //====================================================================//
        // 500 First Ms => Wait 50 Ms More Each Loop
        if (self::$standBy < self::STANDBY_IDLE) {
            self::$standBy += 25;
        }
        //====================================================================//
        // 500 Ms to 1 Second => Wait 100 Ms More Each Loop
        if ((self::STANDBY_IDLE <= self::$standBy) && (self::$standBy < self::STANDBY_MAX)) {
            self::$standBy += 2 * self::STANDBY_MIN;
        }
    }

    /**
     * Check if Worker is IDLE (Waiting for more than 500Ms).
     *
     * @return bool
     */
    public static function isIdle(): bool
    {
        return self::$standBy > self::STANDBY_IDLE;
    }

    /**
     * Clear Worker Loop Standby Counter.
     */
    public static function clearStandBy(): void
    {
        self::$standBy = self::STANDBY_MIN;
    }

    /**
     * Store MicroTime when Task Started
     */
    public static function start(): void
    {
        self::$startedAt = microtime(true);
    }

    /**
     * This Tempo Function is Called when Worker Loop was completed with Job Execution.
     * Ensure a Minimal Task Time of 50Ms
     */
    public static function wipStandBy(): void
    {
        //====================================================================//
        // Evaluate Task Execution delay in Us
        $usDelta = round((microtime(true) - self::$startedAt));
        //====================================================================//
        // Evaluate Remaining Ms to 50Ms
        $msPause = self::STANDBY_MIN - ($usDelta * 1E3);
        if ($msPause > 0) {
            self::msSleep((int) $msPause);
        }
    }
}
