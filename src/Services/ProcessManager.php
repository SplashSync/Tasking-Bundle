<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Services;

use Psr\Log\LoggerInterface;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tools\Timer;

/**
 * Linux Process Manager
 */
class ProcessManager
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     *  Processing Parameters
     */
    const CMD_NOHUP = "/usr/bin/nohup ";                        // Console Command For NoHup
    const CMD_CONSOLE = "bin/console ";                         // Console Command Prefix
    const CMD_SUFIX = "  < /dev/null > /dev/null 2>&1 &";       // Console Command Suffix
    const WORKER = "tasking:worker";                            // Worker Start Console Command
    const SUPERVISOR = "tasking:supervisor";                    // Supervisor Start Console Command
    const CHECK = "tasking:check";                              // Check Start Console Command
    const CRON = "* * * * * ";                                  // Crontab Frequency

    //==============================================================================
    //  Variables Definition
    //==============================================================================

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Sf Project Root Dir
     *
     * @var string
     */
    private string $projectDir;

    //====================================================================//
    //  CONSTRUCTOR
    //====================================================================//

    /**
     * Class Constructor
     *
     * @param string          $rootDir
     * @param LoggerInterface $logger
     */
    public function __construct(string $rootDir, LoggerInterface $logger)
    {
        //====================================================================//
        // Link to Symfony Logger
        $this->logger = $logger;
        //====================================================================//
        // Init Parameters
        $this->projectDir = dirname($rootDir);
    }

    //==============================================================================
    //      Process Operations
    //==============================================================================

    /**
     * Check Crontab Configuration and Update it if Necessary
     *
     * @return string
     */
    public function checkCrontab(): string
    {
        //====================================================================//
        // Check Crontab Management is Activated
        if (!Configuration::isServerForceCrontab()) {
            $this->logger->debug("Process Manager: Crontab is Disabled.");

            return Task::CRONTAB_DISABLED;
        }
        //====================================================================//
        // Compute Expected Cron Tab Command
        $command = self::CRON." ".Configuration::getServerPhpVersion()." ";
        $command .= " ".$this->projectDir."/".self::CMD_CONSOLE;
        $command .= " ".self::CHECK." --env=".Configuration::getEnvironmentName()." ".self::CMD_SUFIX;
        //====================================================================//
        // Read Current Cron Tab Configuration
        $cronTab = array();
        exec("crontab -l > /dev/null 2>&1 &", $cronTab);
        $current = array_shift($cronTab);
        //====================================================================//
        // Update Cron Tab Configuration if Needed
        if ($current !== $command) {
            exec('echo "'.$command.'" > crontab.conf');
            exec("crontab crontab.conf");

            $this->logger->warning("Process Manager: Crontab Updated.");

            return Task::CRONTAB_UPDATED;
        }

        $this->logger->debug("Process Manager: Crontab is Already Ok.");

        return Task::CRONTAB_OK;
    }

    /**
     * Start a Process on Local Machine (Server Node)
     *
     * @param string      $command     Symfony Command to Execute (i.e tasking:start)
     * @param null|string $environment Force Symfony Environment for this Command
     *
     * @return bool
     */
    public function start(string $command, string $environment = null) : bool
    {
        //====================================================================//
        // Select Environment
        $env = is_null($environment) ? Configuration::getEnvironmentName() : $environment;

        //====================================================================//
        // Finalize Command
        $rawCmd = self::CMD_NOHUP.Configuration::getServerPhpVersion()." ";
        $rawCmd .= $this->projectDir."/".self::CMD_CONSOLE;
        $rawCmd .= $command." --env=".$env.self::CMD_SUFIX;

        //====================================================================//
        // Verify This Command Not Already Running
        if ($this->exists($command, $env) > 0) {
            $this->logger->info("Process Manager: Process already active (".$rawCmd.")");

            return true;
        }
        //====================================================================//
        // Execute Command
        exec($rawCmd);
        //====================================================================//
        // Wait for Script Startup
        Timer::msSleep(200);
        //====================================================================//
        // User Info
        $this->logger->notice("Process Manager: Process Started (".$rawCmd.")");

        return true;
    }

    /**
     * Check if a Similar Process Exists on Local Machine (Server Node)
     *
     * @param string      $command
     * @param null|string $environment
     *
     * @return int Count of Process for This Command
     */
    public function exists(string $command, string $environment = null) : int
    {
        //====================================================================//
        // Select Environment
        $env = is_null($environment) ? Configuration::getEnvironmentName() : $environment;

        //====================================================================//
        // Find Command
        $listCommand = Configuration::getServerPhpVersion()." ";
        $listCommand .= $this->projectDir."/".self::CMD_CONSOLE;
        $listCommand .= $command." --env=".$env;

        //====================================================================//
        // Verify This Command Not Already Running
        $list = null;
        $count = (int) exec("pgrep '".$listCommand."' -xfc ", $list);

        //====================================================================//
        // Debug
        $this->logger->info("Process Manager: Count (".$listCommand.") = ".$count);

        return $count;
    }
}
