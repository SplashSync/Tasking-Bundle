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
use Splash\Tasking\Services\Runner;
use Splash\Tasking\Services\SystemManager;
use Splash\Tasking\Services\WorkersManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker Command - Execute Tasks Actions
 */
class WorkerCommand extends Command
{
    //====================================================================//
    // Managers & Tasks Counters
    //====================================================================//

    /**
     * Total of Tasks Treated since Worker Started
     *
     * @var int
     */
    private int $taskTotal = 0;

    /**
     * Workers Manager Service
     *
     * @var WorkersManager
     */
    private WorkersManager $manager;

    /**
     * @var SystemManager
     */
    private SystemManager $system;

    /**
     * Task Runner Service
     *
     * @var Runner
     */
    private Runner $runner;

    /**
     * Class Constructor
     *
     * @param WorkersManager $workerManager
     * @param SystemManager  $system
     * @param Runner         $james
     */
    public function __construct(WorkersManager $workerManager, SystemManager $system, Runner $james)
    {
        parent::__construct('tasking:worker');
        //====================================================================//
        // Link to Worker Manager Service
        $this->manager = $workerManager;
        //====================================================================//
        // Link to System Manager Service
        $this->system = $system;
        //====================================================================//
        // Link to Task Runner Service
        $this->runner = $james;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tasking:worker')
            ->setDescription('Run a Tasking Worker Process ')
            ->addArgument('id', InputArgument::OPTIONAL, 'Process identifier')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Init Console Command & Worker
        $this->boot($input);

        //====================================================================//
        // Worker Tasks Execution Loop
        while (!$this->isToKill($output)) {
            //====================================================================//
            // Run Next Normal or Static Tasks
            if (true === $this->runner->run()) {
                $this->taskTotal++;
            }
            //====================================================================//
            // Refresh Worker Status (WatchDog)
            $this->manager->refresh(false);
        }

        //==============================================================================
        // Set Status as Stopped
        $this->manager->stop();
        //====================================================================//
        // Ensure Release All Token Before Exit
        $this->runner->ensureTokenRelease();

        return 0;
    }

    /**
     * Initialize Worker Process
     *
     * @param InputInterface $input
     *
     * @throws Exception
     */
    private function boot(InputInterface $input): void
    {
        //====================================================================//
        // Load Input Parameters
        $processId = $input->getArgument('id');
        //====================================================================//
        // Safety Checks
        if (!is_scalar($processId) || ($processId <= 0)) {
            throw new Exception('You must provide a proccess Id Number');
        }
        //====================================================================//
        // Init Worker
        $this->manager->initialize((int) $processId);
        //====================================================================//
        // Init System Manager
        $this->system->initSignalHandlers();
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
        if ($this->system->hasStopOrPauseSignal()) {
            $output->writeln("<comment>Stop or Pause Signal Received</comment>");

            return true;
        }

        return $this->manager->isToKill($this->taskTotal);
    }
}
