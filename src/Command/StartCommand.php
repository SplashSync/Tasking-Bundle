<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Command;

use Splash\Tasking\Services\WorkersManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console Command to Start All tasking Worker Background Process
 */
class StartCommand extends Command
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
        parent::__construct('tasking:start');
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
            ->setName('tasking:start')
            ->setDescription('Tasking Service : Start All Supervisors & Workers Process on All Machines')
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
        // User Information
        if ($output->isVerbose()) {
            $output->writeln('<info> Start Supervisor & Workers Process on all found Machines. </info>');
        }
        //====================================================================//
        // Request All Active Workers to Start
        $this->manager->setupAllWorkers(true);
        //====================================================================//
        // Check Supervisors & Crontab
        $this->manager->checkSupervisor();

        return 0;
    }
}
