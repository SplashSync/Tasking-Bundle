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

use Exception;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Services\TasksManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Status Command - Render Live Status of Workers, Tokens & Tasks
 */
class StatusCommand extends ContainerAwareCommand
{
    /**
     * @var ProgressBar
     */
    private $progress;

    /**
     * Tasks Manager Service
     *
     * @var TasksManager
     */
    private $manager;

    /**
     * Class Constructor
     *
     * @param TasksManager $workerManager
     */
    public function __construct(TasksManager $workerManager)
    {
        parent::__construct(null);
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
            ->setName('tasking:status')
            ->setDescription('Tasking Service : Check Status of Tasking Services')
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
            $this->showHead($output);
            $this->showWorkers($output);
        }
        //====================================================================//
        // Load Tasks Repository
        $repo = $this->manager->getTasksRepository();

        while (1) {
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
            sleep(1);
        }
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
     */
    protected function showWorkers(OutputInterface $output): void
    {
        //====================================================================//
        // Load Tasks Repository
        $repo = $this->manager->getWorkerRepository();

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
        $workers = $this->manager->getWorkerRepository();
        //====================================================================//
        // Fetch Workers Status
        $status = $workers->getWorkersStatus();
        //====================================================================//
        // IF No Worker is Running
        if ($status["running"] < 1) {
            return ' <error>No Worker Running!</error>';
        }
        //====================================================================//
        // IF No Supervisor is Running
        if ($status["supervisor"] < 1) {
            return ' <error>No Supervisor Running!</error>';
        }
        //====================================================================//
        // Generate Response String
        $response = $status["running"].'/'.$status["total"].' Workers ';
        $response .= $status["supervisor"].' Supervisors';

        return ' <info>'.$response.'</info>';
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
            $message .= ' <info>'.'All Done! '.'</info>';
        } else {
            $message .= ' <comment>'.($total - $finished).' Tasks Pending... '.'</comment>';
        }
        if ($token > 0) {
            $message .= ' <comment>'.$token.' Tokens Used...'.'</comment>';
        }

        return $message;
    }
}
