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

namespace Splash\Tasking\Tests\Controller;

use PHPUnit\Framework\Assert;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;
use Symfony\Component\Process\Process;

/**
 * Test CronTab & Process Manager
 */
class B002ProcessControllerTest extends AbstractTestController
{
    /**
     * Test of Linux Crontab management
     */
    public function testCronTab(): void
    {
        //====================================================================//
        // CHECK if Crontab Management is Active
        //====================================================================//
        $config = $this->getContainer()->getParameter('splash_tasking');
        if (!$config["server"]["force_crontab"]) {
            Assert::assertNotEmpty($this->process->checkCrontab());
            Assert::assertTrue($this->worker->checkSupervisor());
            sleep(5);

            return;
        }

        //====================================================================//
        // DELETE Crontab Configuration
        //====================================================================//

        exec('crontab -r > /dev/null 2>&1 &');

        //====================================================================//
        // CHECK CRONTAB CONFIG
        //====================================================================//

        $this->process->checkCrontab();

        //====================================================================//
        // VERIFY ALL PROCESS ARE STOPPED
        //====================================================================//

        //====================================================================//
        // Read Current Cron Tab Configuration
        $cronTab = array();
        exec("crontab -l", $cronTab);
        Assert::assertCount(1, $cronTab);
        Assert::assertInternalType("string", array_shift($cronTab));
    }

    /**
     * Test of Task Inputs
     */
    public function testStopCommand(): void
    {
        //====================================================================//
        // REQUEST STOP OF ALL PROCESS
        //====================================================================//

        $this->doStopCommand(false);

        //====================================================================//
        // VERIFY ALL PROCESS ARE STOPPED
        //====================================================================//

        //====================================================================//
        // Load Worker Reprository
        $workers = $this->workersRepository->findAll();

        //====================================================================//
        // Workers List Shall not be Empty at this Step of Tests
        Assert::assertNotEmpty($workers);

        //====================================================================//
        // Check all Workers are Stopped
        foreach ($workers as $worker) {
            Assert::assertInstanceOf(Worker::class, $worker);
            Assert::assertFalse($worker->isRunning());
            Assert::assertNotEmpty($worker->getLastSeen());
            Assert::assertFalse($this->doCheckProcessIsAlive($worker->getPid()));
        }

        $this->entityManager->clear();
    }

    /**
     * Test of Tasking Worker Process Aativation
     */
    public function testSupervisorIsRunning(): void
    {
        //====================================================================//
        // REQUEST START OF SUPERVISOR
        //====================================================================//

        $this->worker->checkSupervisor();
        sleep(3);

        //====================================================================//
        // CHECK SUPERVISOR is RUNNING
        //====================================================================//

        $this->entityManager->clear();

        $supervisor = $this->workersRepository->findOneByProcess(0);
        Assert::assertInstanceOf(Worker::class, $supervisor);
        Assert::assertTrue($supervisor->isRunning());

        //====================================================================//
        // CHECK EXPECTED WORKERS are RUNNING
        //====================================================================//

        $config = $this->getContainer()->getParameter('splash_tasking')["supervisor"];

        //====================================================================//
        // Load Workers for Local Supervisor
        $workers = $this->workersRepository->findBy(array(
            "nodeName" => $supervisor->getNodeName(),
            "running" => 1,
        ));

        //====================================================================//
        // Verify Workers Count
        Assert::assertEquals($config['max_workers'] + 1, count($workers));

        //====================================================================//
        // Verify all Workers are Alive
        foreach ($workers as $worker) {
            $refreshedWorker = $this->workersRepository->find($worker->getId());
            Assert::assertInstanceOf(Worker::class, $refreshedWorker);
            $this->entityManager->refresh($refreshedWorker);
            Assert::assertTrue($refreshedWorker->isRunning());
            Assert::assertNotEmpty($refreshedWorker->getLastSeen());
            Assert::assertTrue($this->worker->isRunning($refreshedWorker->getProcess()));
            Assert::assertTrue($this->doCheckProcessIsAlive($refreshedWorker->getPid()));
        }
    }

    /**
     * Test of Worker Disable Feature
     */
    public function testWorkerIsDisabled(): void
    {
        //====================================================================//
        // DISABLE & STOP ALL WORKERS
        //====================================================================//

        $this->doStopCommand(true);

        //====================================================================//
        // Save to database
        $this->entityManager->clear();

        //====================================================================//
        // RESTART ALL WORKERS
        //====================================================================//

        $this->worker->checkSupervisor();
        sleep(2);

        //====================================================================//
        // VERIFY ALL WORKERS ARE OFF
        //====================================================================//

        //====================================================================//
        // Load Local Supervisor
        $this->entityManager->clear();
        $supervisor = $this->workersRepository->findOneByProcess(0);
        Assert::assertInstanceOf(Worker::class, $supervisor);

        //====================================================================//
        // Load Workers for Local Supervisor
        $workers = $this->workersRepository->findBy(
            array("nodeName" => $supervisor->getNodeName())
        );

        /**
         * Check all Workers
         *
         * @var Worker $worker
         */
        foreach ($workers as $worker) {
            $refreshedWorker = $this->workersRepository->find($worker->getId());

            Assert::assertInstanceOf(Worker::class, $refreshedWorker);
            $this->entityManager->refresh($refreshedWorker);
            Assert::assertTrue($this->worker->isRunning($refreshedWorker->getProcess()));
            Assert::assertFalse($refreshedWorker->isEnabled());
            Assert::assertFalse($this->doCheckProcessIsAlive($refreshedWorker->getPid()));
            Assert::assertFalse($worker->isRunning());
        }

        //====================================================================//
        // RESTART ALL WORKERS
        //====================================================================//

        $this->doStopCommand(false);
        $this->worker->checkSupervisor();
        sleep(2);
    }

    /**
     * Execute Stop Console Command
     *
     * @param bool $noRestart
     */
    private function doStopCommand(bool $noRestart): void
    {
        //====================================================================//
        // Create Command
        $command = "php bin/console tasking:stop --env=test -vv".($noRestart? " --no-restart" : null);
        //====================================================================//
        // Execute Test (SF 3.4 Versions)
        $process = new Process($command);
        //====================================================================//
        // Execute Test (SF 4 Versions)
//        $process = Process::fromShellCommandline($command);
        //====================================================================//
        // Clean Working Dir
        $workingDirectory = (string) $process->getWorkingDirectory();
        if (strrpos($workingDirectory, "/web") == (strlen($workingDirectory) - 4)) {
            $process->setWorkingDirectory(substr($workingDirectory, 0, strlen($workingDirectory) - 4));
        } elseif (strrpos($workingDirectory, "/app") == (strlen($workingDirectory) - 4)) {
            $process->setWorkingDirectory(substr($workingDirectory, 0, strlen($workingDirectory) - 4));
        }
        //====================================================================//
        // Run Shell Command
        $process->mustRun();
    }

    /**
     * Check Process is Running
     *
     * @param int $pid
     *
     * @return bool
     */
    private function doCheckProcessIsAlive(int $pid) : bool
    {
        //====================================================================//
        // Init Result Array
        $list = array();
        //====================================================================//
        // Execute Seach Command
        exec("ps ".$pid, $list);
        //====================================================================//
        // Check Result
        return (count($list) > 1) ? true : false;
    }
}
