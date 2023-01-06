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

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Access to system features
 * - Signal Monitoring
 * - Apt Updates detection
 */
class SystemManager
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Get Last Stop System Received
     *
     * @var null|int
     */
    private ?int $stopSignal = null;

    /**
     * Get Last Pause System Received
     *
     * @var null|int
     */
    private ?int $pauseSignal = null;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @param LoggerInterface $logger
     *
     * @throws Exception
     */
    public function __construct(LoggerInterface $logger)
    {
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
    }

    //====================================================================//
    // GLOBAL METHODS
    //====================================================================//

    /**
     * Class Constructor
     *
     * @throws Exception
     */
    public function initSignalHandlers(): self
    {
        //====================================================================//
        // Register Signals Handlers
        $this->registerStopSignalHandlers();
        $this->registerPauseSignalHandlers();

        return $this;
    }

    /**
     * Get Signal Status String
     *
     * @throws Exception
     *
     * @return string
     */
    public function getSignalsStatus(): string
    {
        if ($this->hasStopSignal()) {
            return ' <comment>'.self::toSignalString((int) $this->getStopSignal()).'</comment>';
        }
        if ($this->hasSystemLock()) {
            return ' <comment>System Lock is Active</comment>';
        }
        if ($this->hasPauseSignal()) {
            return ' <comment>'.self::toSignalString((int) $this->getPauseSignal()).'</comment>';
        }

        return  "";
    }

    /**
     * A Stop or Pause Signal was Received
     *
     * @return bool
     */
    public function hasStopOrPauseSignal(): bool
    {
        return $this->hasStopSignal() || $this->hasPauseSignal();
    }

    //====================================================================//
    // STOP SIGNALS MANAGEMENT
    //====================================================================//

    /**
     * Setup Stop Signal Handlers
     *
     * @throws Exception
     *
     * @return void
     */
    public function registerStopSignalHandlers(): void
    {
        //====================================================================//
        // Safety Check
        if (!extension_loaded("pcntl")) {
            throw new Exception(
                "Tasking Bundle now require pcntl extension."
            );
        }
        //====================================================================//
        // Init Signal Flags
        $this->stopSignal = null;
        //====================================================================//
        // Create Callable
        $callable = array($this, 'onStopSignal');
        //====================================================================//
        // Register Stop Signal Handlers
        foreach (array_keys(self::getStopSignals()) as $signal) {
            pcntl_signal($signal, $callable);
        }
        //====================================================================//
        // Force Async Mode
        pcntl_async_signals(true);
    }

    /**
     * A Stop Signal was received
     *
     * @param int $signal
     *
     * @throws Exception
     *
     * @return void
     */
    public function onStopSignal(int $signal): void
    {
        $this->stopSignal = $signal;
        $this->logger->warning(self::toSignalString($signal));
    }

    /**
     * A Stop Signal was Received
     *
     * @return bool
     */
    public function hasStopSignal(): bool
    {
        return isset($this->stopSignal);
    }

    /**
     * Get Last Received Stop Signal
     *
     * @return null|int
     */
    public function getStopSignal(): ?int
    {
        return $this->stopSignal ?? null;
    }

    //====================================================================//
    // PAUSE SIGNALS MANAGEMENT
    //====================================================================//

    /**
     * Setup Pause Signal Handlers
     *
     * @throws Exception
     *
     * @return void
     */
    public function registerPauseSignalHandlers(): void
    {
        //====================================================================//
        // Safety Check
        if (!extension_loaded("pcntl")) {
            throw new Exception(
                "Tasking Bundle now require pcntl extension."
            );
        }
        //====================================================================//
        // Init Signal Flags
        $this->pauseSignal = null;
        //====================================================================//
        // Create Callable
        $callable = array($this, 'onPauseSignal');
        //====================================================================//
        // Register Stop Signal Handlers
        foreach (array_keys(self::getPauseSignals()) as $signal) {
            pcntl_signal($signal, $callable);
        }
        //====================================================================//
        // Force Async Mode
        pcntl_async_signals(true);
    }

    /**
     * A Stop Signal was received
     *
     * @param int $signal
     *
     * @throws Exception
     *
     * @return void
     */
    public function onPauseSignal(int $signal): void
    {
        $this->pauseSignal = $signal;
        $this->logger->warning(self::toSignalString($signal));
    }

    /**
     * A Stop Signal was Received
     *
     * @return bool
     */
    public function hasPauseSignal(): bool
    {
        return isset($this->pauseSignal) || $this->hasSystemLock();
    }

    /**
     * Get Last Received Stop Signal
     *
     * @return null|int
     */
    public function getPauseSignal(): ?int
    {
        return $this->pauseSignal ?? null;
    }

    /**
     * Check if System Lock is Enable
     *
     * @return bool
     */
    public function hasSystemLock(): bool
    {
        $processes = array(
            "apt" => "Apt Get",
            "dpkg" => "DPKG",
        );

        foreach (array_keys($processes) as $process) {
            //====================================================================//
            // Verify This Command Not Already Running
            $list = null;
            $count = (int) exec(sprintf("pgrep %s -c -a", $process), $list);
            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get List of Tracked Stop Signal
     *
     * @throws Exception
     *
     * @return array<int, string>
     */
    private static function getStopSignals(): array
    {
        static $signals;

        if (!isset($signals)) {
            //====================================================================//
            // Safety Check
            if (!extension_loaded("pcntl")) {
                throw new Exception(
                    "Tasking Bundle now require pcntl extension."
                );
            }
            $signals = array(
                SIGTERM => "Terminate",
                SIGINT => "Interrupt",
                SIGQUIT => "Quit",
                SIGALRM => "Alarm"
            );
        }

        return $signals;
    }

    /**
     * Get List of Tracked Stop Signal
     *
     * @throws Exception
     *
     * @return array<int, string>
     */
    private static function getPauseSignals(): array
    {
        return array();
    }

    /**
     * A Stop Signal was received
     *
     * @param int $signal
     *
     * @throws Exception
     *
     * @return string
     */
    private static function toSignalString(int $signal): string
    {
        return sprintf(
            "[%d] %s Signal Received",
            $signal,
            self::getStopSignals()[$signal] ?? self::getPauseSignals()[$signal] ?? $signal
        );
    }
}
