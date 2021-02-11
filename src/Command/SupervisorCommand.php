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

namespace Splash\Tasking\Command;

use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Services\SupervisorsManager;
use Splash\Tasking\Services\TasksManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Supervisor Command - Manager Workers
 */
class SupervisorCommand extends ContainerAwareCommand
{
    /**
     * Supervisor Manager Service
     *
     * @var SupervisorsManager
     */
    private $manager;

    /**
     * Tasks Manager Service
     *
     * @var TasksManager
     */
    private $tasks;

    /**
     * Class Constructor
     *
     * @param SupervisorsManager $supervisorsManager
     * @param TasksManager       $tasksManager
     */
    public function __construct(SupervisorsManager $supervisorsManager, TasksManager $tasksManager)
    {
        parent::__construct();
        //====================================================================//
        // Link to Supervisor Manager Service
        $this->manager = $supervisorsManager;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //====================================================================//
        // Initialize Supervisor Worker
        $this->boot();

        //====================================================================//
        // Run Supervisor Loop
        while (!$this->manager->isToKill(null)) {
            //====================================================================//
            // Refresh Status of Each Worker
            $this->manager->doSupervision();
            //====================================================================//
            // Clean All Old Tasks
            $this->tasks->cleanUp();
            //====================================================================//
            // Refresh Worker Status (WatchDog)
            $this->manager->refresh(false);
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
     */
    private function boot(): void
    {
        //====================================================================//
        // Init Worker
        $this->manager->initialize(0);
        $this->manager->getMaxWorkers();
        //====================================================================//
        // Init Static Tasks List
        $this->tasks->loadStaticTasks();
        //====================================================================//
        // Setup PHP Error Reporting Level
        error_reporting(E_ERROR);
    }
}
