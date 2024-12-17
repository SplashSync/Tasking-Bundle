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

use BadPixxel\Tasking\Services\Runner;
use BadPixxel\Tasking\Services\SystemManager;
use BadPixxel\Tasking\Services\WorkersManager;
use Exception;
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
     * Service Constructor
     */
    public function __construct(
        private readonly WorkersManager $workersManager,
        private readonly SystemManager  $systemManager,
        private readonly Runner         $runner
    ) {
        parent::__construct();
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
            $this->workersManager->refresh(false);
        }

        //==============================================================================
        // Set Status as Stopped
        $this->workersManager->stop();
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
            throw new Exception('You must provide a process Id Number');
        }
        //====================================================================//
        // Init Worker
        $this->workersManager->initialize((int) $processId);
        //====================================================================//
        // Init System Manager
        $this->systemManager->initSignalHandlers();
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
        if ($this->systemManager->hasStopOrPauseSignal()) {
            $output->writeln("<comment>Stop or Pause Signal Received</comment>");

            return true;
        }

        return $this->workersManager->isToKill($this->taskTotal);
    }
}
