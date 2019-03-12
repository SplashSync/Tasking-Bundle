<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
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
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Services\TaskingService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Splash\Tasking\Services\WorkersManager;

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
    private $manager;

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
     * Timeout for Worker Stop
     *
     * @var DateTime
     */
    private $timeout;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('tasking:stop')
            ->setDescription('Tasking Service : Stop All Supervisors & Workers Process on All Machines')
            ->addOption('no-restart', null, InputOption::VALUE_OPTIONAL, 'Do you want to Restart Workers After Stop?', false)
        ;
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
        // Count Total Number of Wrokers
        $total = $this->manager->countActiveWorkers();
        //====================================================================//
        // Track Workers are Stopped
        while (($count = $this->manager->countActiveWorkers()) && !$this->isInTimeout()) {
            //====================================================================//
            // User Information
            $output->writeln('<info> Still '.$count.' Actives Workers Process... </info>');
            //====================================================================//
            // Request All Active Workers to Stop
            $this->manager->setupAllWorkers(false);
            //====================================================================//
            // Pause
            sleep(1);
        }
        //====================================================================//
        // Check if User Asked to Restart Workers or NOT
        if ($input->hasParameterOption('--no-restart')) {
            $output->writeln('<question>'.$total.' Workers now Sleeping... </question>');

            return null;
        }
        //====================================================================//
        // Request All Active Workers to Restart
        $this->manager->setupAllWorkers(true);
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
