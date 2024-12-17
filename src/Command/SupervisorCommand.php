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

namespace BadPixxel\Tasking\Command;

use BadPixxel\Tasking\Services\SupervisorsManager;
use BadPixxel\Tasking\Services\SystemManager;
use BadPixxel\Tasking\Services\Tasks\StaticTasksUpdater;
use BadPixxel\Tasking\Services\TasksManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Supervisor Command - Manager Workers
 */
class SupervisorCommand extends Command
{
    /**
     * Command Constructor
     */
    public function __construct(
        private readonly SupervisorsManager $supervisorsManager,
        private readonly SystemManager      $systemManager,
        private readonly StaticTasksUpdater $staticTasksUpdater,
        private readonly TasksManager $tasksManager
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        //====================================================================//
        // Init Supervisor Command
        $this
            ->setName('tasking:supervisor')
            ->setDescription('Run a Supervisor Worker Process ')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(UnusedFormalParameter)
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Initialize Supervisor Worker
        $this->boot();

        //====================================================================//
        // Run Supervisor Loop
        while (!$this->isToKill($output)) {
            //====================================================================//
            // Ensure System is NOT Paused
            if (!$this->systemManager->hasPauseSignal()) {
                //====================================================================//
                // Refresh Status of Each Worker
                $this->supervisorsManager->doSupervision();
                //====================================================================//
                // Clean All Old Tasks
                $this->tasksManager->cleanUp();
                //====================================================================//
                // Refresh Worker Status (WatchDog)
                $this->supervisorsManager->refresh(false);
            }
            //====================================================================//
            // Wait
            $this->supervisorsManager->doPause();
        }
        //==============================================================================
        // Set Status as Stopped
        $this->supervisorsManager->stop();

        return 0;
    }

    /**
     * Init Supervisor & Services
     *
     * @throws Exception
     */
    private function boot(): void
    {
        //====================================================================//
        // Init Worker
        $this->supervisorsManager->initialize(0);
        $this->supervisorsManager->getMaxWorkers();
        //====================================================================//
        // Init System Manager
        $this->systemManager->initSignalHandlers();
        //====================================================================//
        // Init Static Tasks List
        $this->staticTasksUpdater->loadStaticTasks();
        //====================================================================//
        // Setup PHP Error Reporting Level
        error_reporting(E_ERROR);
    }

    /**
     * Check if Worker is to Kill
     *
     * @param OutputInterface $output
     *
     * @throws Exception
     *
     * @return bool
     */
    private function isToKill(OutputInterface $output): bool
    {
        if ($this->systemManager->hasStopSignal()) {
            $output->writeln("<comment>Stop Signal Received</comment>");

            return true;
        }

        return $this->supervisorsManager->isToKill(null);
    }
}
