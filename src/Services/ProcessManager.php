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
use BadPixxel\Tasking\Helper\Timer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Linux Process Manager
 */
#[Autoconfigure(bind: array(
    '$projectDir' => '%kernel.project_dir%',
))]
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

    /**
     * Service Constructor
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly Configuration $configuration,
        private readonly LoggerInterface $logger
    ) {
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
        if (!$this->configuration->isServerForceCrontab()) {
            $this->logger->debug("Process Manager: Crontab is Disabled.");

            return Task::CRONTAB_DISABLED;
        }
        //====================================================================//
        // Compute Expected Cron Tab Command
        $command = self::CRON." ".$this->configuration->getServerPhpVersion()." ";
        $command .= " ".$this->projectDir."/".self::CMD_CONSOLE;
        $command .= " ".self::CHECK." --env=".$this->configuration->getEnvironmentName()." ".self::CMD_SUFIX;
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
        $env = is_null($environment) ? $this->configuration->getEnvironmentName() : $environment;

        //====================================================================//
        // Finalize Command
        $rawCmd = self::CMD_NOHUP.$this->configuration->getServerPhpVersion()." ";
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
        $env = is_null($environment) ? $this->configuration->getEnvironmentName() : $environment;

        //====================================================================//
        // Find Command
        $listCommand = $this->configuration->getServerPhpVersion()." ";
        $listCommand .= $this->projectDir."/".self::CMD_CONSOLE;
        $listCommand .= $command." --env=".$env;

        //====================================================================//
        // Verify This Command Not Already Running
        $list = null;
        $count = (int) exec("pgrep '".$listCommand."' -xf | wc -l", $list);

        //====================================================================//
        // Debug
        $this->logger->info("Process Manager: Count (".$listCommand.") = ".$count);

        return $count;
    }
}
