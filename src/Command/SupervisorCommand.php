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

namespace Splash\Tasking\Command;

use Exception;
use Splash\Tasking\Services\SupervisorsManager;
use Splash\Tasking\Services\SystemManager;
use Splash\Tasking\Services\TasksManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Supervisor Command - Manager Workers
 */
class SupervisorCommand extends Command
{
    /**
     * Supervisor Manager Service
     *
     * @var SupervisorsManager
     */
    private SupervisorsManager $manager;

    /**
     * @var SystemManager
     */
    private SystemManager $system;

    /**
     * Tasks Manager Service
     *
     * @var TasksManager
     */
    private TasksManager $tasks;

    /**
     * Class Constructor
     *
     * @param SupervisorsManager $supervisorsManager
     * @param SystemManager      $system
     * @param TasksManager       $tasksManager
     */
    public function __construct(
        SupervisorsManager $supervisorsManager,
        SystemManager $system,
        TasksManager $tasksManager
    ) {
        parent::__construct('tasking:supervisor');
        //====================================================================//
        // Link to Supervisor Manager Service
        $this->manager = $supervisorsManager;
        //====================================================================//
        // Link to System Manager Service
        $this->system = $system;
        //====================================================================//
        // Link to Tasks Manager
        $this->tasks = $tasksManager;
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
            if (!$this->system->hasPauseSignal()) {
                //====================================================================//
                // Refresh Status of Each Worker
                $this->manager->doSupervision();
                //====================================================================//
                // Clean All Old Tasks
                $this->tasks->cleanUp();
                //====================================================================//
                // Refresh Worker Status (WatchDog)
                $this->manager->refresh(false);
            }
            //====================================================================//
            // Wait
            $this->manager->doPause();
        }
        //==============================================================================
        // Set Status as Stopped
        $this->manager->stop();

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
        $this->manager->initialize(0);
        $this->manager->getMaxWorkers();
        //====================================================================//
        // Init System Manager
        $this->system->initSignalHandlers();
        //====================================================================//
        // Init Static Tasks List
        $this->tasks->loadStaticTasks();
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
        if ($this->system->hasStopSignal()) {
            $output->writeln("<comment>Stop Signal Received</comment>");

            return true;
        }

        return $this->manager->isToKill(null);
    }
}
