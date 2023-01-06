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

use DateTime;
use Exception;
use Splash\Tasking\Services\WorkersManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Workers Stop Command - Ask All Workers to Stop
 */
class StopCommand extends Command
{
    /**
     * Workers Manager Service
     *
     * @var WorkersManager
     */
    private WorkersManager $manager;

    /**
     * Timeout for Worker Stop
     *
     * @var DateTime
     */
    private DateTime $timeout;

    /**
     * Class Constructor
     *
     * @param WorkersManager $workerManager
     */
    public function __construct(WorkersManager $workerManager)
    {
        parent::__construct('tasking:stop');
        //====================================================================//
        // Link to Worker Manager Service
        $this->manager = $workerManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tasking:stop')
            ->setDescription('Tasking Service : Stop All Supervisors & Workers Process on All Machines')
            ->addOption(
                'no-restart',
                null,
                InputOption::VALUE_OPTIONAL,
                'Do you want to Restart Workers After Stop?',
                false
            )
        ;
        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // User Information
        if ($output->isVerbose()) {
            $output->writeln('<info> Stop Supervisor & Workers Process on all found Machines. </info>');
        }
        //====================================================================//
        // Request All Active Workers to Stop
        $this->manager->setupAllWorkers(false);
        //====================================================================//
        // Setup TimeOut for this operation
        $this->setupTimeout();
        //====================================================================//
        // Count Total Number of Workers
        $total = $this->manager->countActiveWorkers();
        //====================================================================//
        // Track Workers are Stopped
        $count = $total;
        while (($count > 0) && !$this->isInTimeout()) {
            //====================================================================//
            // User Information
            $output->writeln('<info> Still '.$count.' Actives Workers Process... </info>');
            //====================================================================//
            // Request All Active Workers to Stop
            $this->manager->setupAllWorkers(false);
            //====================================================================//
            // Pause
            sleep(1);
            $count = $this->manager->countActiveWorkers();
        }
        //====================================================================//
        // Check if User Asked to Restart Workers or NOT
        if ($input->hasParameterOption('--no-restart')) {
            $output->writeln('<question>'.$total.' Workers now Sleeping... </question>');

            return 0;
        }
        //====================================================================//
        // Request All Active Workers to Restart
        $this->manager->setupAllWorkers(true);

        return 0;
    }

    /**
     * Configure Commande Timeout
     */
    private function setupTimeout() : void
    {
        $this->timeout = new DateTime("- 30 Seconds");
    }

    /**
     * Check if Command Timeout is Exceded
     *
     * @return bool
     */
    private function isInTimeout() : bool
    {
        return ($this->timeout->getTimestamp() > (new DateTime())->getTimestamp());
    }
}
