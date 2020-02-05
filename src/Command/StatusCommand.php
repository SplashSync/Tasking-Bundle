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

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\WorkerRepository;
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
        /** @var TaskRepository $repo */
        $repo = $this->getContainer()
            ->get("doctrine")->getManager()
            ->getRepository(Task::class);

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
        /** @var WorkerRepository $repo */
        $repo = $this->getContainer()
            ->get("doctrine")->getManager()
            ->getRepository(Worker::class);

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
     * @return string
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function getWorkersStatusStr()
    {
        //====================================================================//
        // Load Worker Repository
        $workers = $this->getContainer()
            ->get("doctrine")->getManager()
            ->getRepository(Worker::class);
        $workers->clear();

        //====================================================================//
        // Init Counters
        $disabled = 0;
        $sleeping = 0;
        $running = 0;
        $supervisor = 0;

        //====================================================================//
        // Update Workers Counters
        /** @var Worker $worker */
        foreach ($workers->findAll() as $worker) {
            //====================================================================//
            // Workers is Supervisor
            if ((0 == $worker->getProcess()) && $worker->isRunning()) {
                $supervisor++;
            }
            if ((0 == $worker->getProcess())) {
                continue;
            }
            //====================================================================//
            // Workers is Disabled
            if ($worker->isRunning()) {
                $running++;
            } elseif (!$worker->isEnabled()) {
                $disabled++;
            } else {
                $sleeping++;
            }
        }
        //====================================================================//
        // IF No Worker is Running
        if ($running < 1) {
            return ' <error>No Worker Running!</error>';
        }
        //====================================================================//
        // IF No Supervisor is Running
        if ($supervisor < 1) {
            return ' <error>No Supervisor Running!</error>';
        }
        //====================================================================//
        // Generate Response String
        $response = $running.'/'.($disabled + $sleeping + $running).' Workers ';
        $response .= $supervisor.' Supervisors';

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
    private function updateProgressBarr(OutputInterface $output, int $pending, int $total, string $status, string $workers) : void
    {
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
