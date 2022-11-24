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

use Exception;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Services\Configuration;
use Splash\Tasking\Services\SystemManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Status Command - Render Live Status of Workers, Tokens & Tasks
 */
class StatusCommand extends Command
{
    /**
     * @var ProgressBar
     */
    private ProgressBar $progress;

    /**
     * @var bool
     */
    private bool $hasRunningWorkers = true;

    /**
     * @var SystemManager
     */
    private SystemManager $systemManager;

    /**
     * Class Constructor
     *
     * @param SystemManager $systemManager
     */
    public function __construct(SystemManager $systemManager)
    {
        parent::__construct('tasking:status');
        //====================================================================//
        // Link to System Manager Service
        $this->systemManager = $systemManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tasking:status')
            ->setDescription('Tasking Service : Check Status of Tasking Services')
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
        // User Information
        if ($output->isVerbose()) {
            $this->showHead($output);
            $this->showWorkers($output);
        }
        //====================================================================//
        // Load Tasks Repository
        $repo = Configuration::getTasksRepository();
        $isToKill = false;
        while (!$isToKill) {
            //====================================================================//
            // Ensure System is NOT Paused
            try {
                //====================================================================//
                // Fetch Tasks Summary
                $repo->clear();
                $status = $repo->getTasksSummary();
                //====================================================================//
                // Update Tasking Progress Bar
                $this->updateProgressBarr(
                    $output,
                    $status['Finished'],
                    $status['Total'],
                    $this->getTasksStatusStr($status['Finished'], $status['Total'], $status['Token']),
                    $this->getWorkersStatusStr()
                );
            } catch (Throwable $ex) {
                sleep(1);
            }
            sleep(1);
            //====================================================================//
            // Update To Kill Flag
            if ($this->systemManager->hasStopSignal() && !$this->hasRunningWorkers) {
                $isToKill = true;
            }
        }

        return 0;
    }

    /**
     * Render Status Command Splash Screen
     *
     * @param OutputInterface $output
     */
    protected function showHead(OutputInterface $output): void
    {
        $output->writeln('==============================================');
        $output->writeln('=          Tasking Bundle Status             =');
        $output->writeln('==============================================');
        $output->writeln('');
    }

    /**
     * Render Workers Status
     *
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @throws Exception
     */
    protected function showWorkers(OutputInterface $output): void
    {
        //====================================================================//
        // Load Tasks Repository
        $repo = Configuration::getWorkerRepository();

        //====================================================================//
        // List Workers Status
        $output->writeln('==============================================');
        $output->writeln('= Workers : ');
        $disabled = 0;
        /** @var Worker $worker */
        foreach ($repo->findAll() as $worker) {
            //====================================================================//
            // Workers is Disabled
            if (!$worker->isEnabled()) {
                $disabled++;
                $workerStatus = 'Disabled';
            } elseif ($worker->isRunning()) {
                $workerStatus = 'Running';
            } else {
                $workerStatus = 'Sleeping';
            }
            //====================================================================//
            // Workers is Enabled
            $status = '===> '.$worker->getNodeName();
            $status .= ' ('.$worker->getPid().':'.$workerStatus.')';
            if ($worker->isEnabled()) {
                $status .= ' : '.$worker->getTask();
            }
            $output->writeln($status);
        }
        $output->writeln('===> '.$disabled.' Workers are Disabled');
        $output->writeln('==============================================');

        $output->writeln('');
    }

    /**
     * Get Worker Status String
     *
     * @throws Exception
     *
     * @return string
     */
    protected function getWorkersStatusStr(): string
    {
        //====================================================================//
        // Load Worker Repository
        $workers = Configuration::getWorkerRepository();
        //====================================================================//
        // Fetch Workers Status
        $status = $workers->getWorkersStatus();
        //====================================================================//
        // Generate Signals String
        $signalsStatus = $this->systemManager->getSignalsStatus();
        //====================================================================//
        // IF No Worker is Running
        if ($status["running"] < 1) {
            $this->hasRunningWorkers = false;

            return ' <error>No Worker Running!</error>'.$signalsStatus;
        }
        //====================================================================//
        // IF No Supervisor is Running
        if ($status["supervisor"] < 1) {
            return ' <error>No Supervisor Running!</error>'.$signalsStatus;
        }
        //====================================================================//
        // Generate Response String
        $response = $status["running"].'/'.$status["total"].' Workers ';
        $response .= $status["supervisor"].' Supervisors';

        return ' <info>'.$response.'</info>'.$signalsStatus;
    }

    /**
     * Update Rendering of Progress Bar
     *
     * @param OutputInterface $output
     * @param int             $pending
     * @param int             $total
     * @param string          $status
     * @param string          $workers
     */
    private function updateProgressBarr(
        OutputInterface $output,
        int $pending,
        int $total,
        string $status,
        string $workers
    ) : void {
        //====================================================================//
        // delete current progress bar
        if (isset($this->progress)) {
            $this->progress->clear();
        }
        //====================================================================//
        // create a new progress bar
        $this->progress = new ProgressBar($output, $total);
        $this->progress->setBarCharacter('<fg=cyan>=</>');
        $this->progress->setProgressCharacter('<fg=red>|</>');
        $this->progress->setFormat(">>%status% \n>> Tasks : [%bar%] %current%/%max% %percent:3s%% \n>>%workers%");
        $this->progress->setMessage($status, "status");
        $this->progress->setMessage($workers, "workers");
        $this->progress->start();
        $this->progress->setProgress($pending);
    }

    /**
     * Get Tasks Status String
     *
     * @param int $finished
     * @param int $total
     * @param int $token
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getTasksStatusStr(int $finished, int $total, int $token): string
    {
        $message = "";
        if ($finished >= $total) {
            return ' <info>'.'All Done! '.'</info>';
        }
        $message .= ($total - $finished).' Tasks... ';

        if ($token > 0) {
            $message .= $token.' Tokens... ';
        }
        if (($finished < $total) && (Configuration::getTasksDeleteDelay() > 0)) {
            $speed = sprintf("%.02f", 60 * $finished / Configuration::getTasksDeleteDelay());
            $message .= $speed.' Tasks/min';
        }

        return ' <comment>'.$message.'</comment>';
    }
}
