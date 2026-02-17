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

namespace BadPixxel\Tasking\Tests\Controller;

use BadPixxel\Tasking\Entity\Worker;
use Exception;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\Process;

/**
 * Test CronTab & Process Manager
 */
class B002ProcessControllerTest extends AbstractTestController
{
    /**
     * Test of Linux Crontab management
     *
     * @throws Exception
     */
    public function testCronTab(): void
    {
        //====================================================================//
        // CHECK if Crontab Management is Active
        //====================================================================//
        $config = $this->getContainer()->getParameter('badpixxel_tasking');
        Assert::assertIsArray($config);
        if (!$config["server"]["force_crontab"]) {
            Assert::assertNotEmpty($this->getProcessManager()->checkCrontab());
            Assert::assertTrue($this->getWorkersManager()->checkSupervisor());
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

        $this->getProcessManager()->checkCrontab();

        //====================================================================//
        // VERIFY ALL PROCESS ARE STOPPED
        //====================================================================//

        //====================================================================//
        // Read Current Cron Tab Configuration
        $cronTab = array();
        exec("crontab -l", $cronTab);
        Assert::assertCount(1, $cronTab);
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
        // Load Worker Repository
        $workers = $this->workersRepository->findAll();

        //====================================================================//
        // Workers List Shall not be Empty at this Step of Tests
        Assert::assertNotEmpty($workers);

        //====================================================================//
        // Check all Workers are Stopped
        foreach ($workers as $worker) {
            Assert::assertFalse($worker->isRunning());
            Assert::assertNotEmpty($worker->getLastSeen());
            Assert::assertFalse($this->doCheckProcessIsAlive($worker->getPid()));
        }

        $this->entityManager->clear();
    }

    /**
     * Test of Tasking Worker Process Activation
     *
     * @throws Exception
     */
    public function testSupervisorIsRunning(): void
    {
        $manager = $this->getWorkersManager();
        //====================================================================//
        // REQUEST START OF SUPERVISOR
        //====================================================================//

        $manager->checkSupervisor();
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

        $config = $this->getContainer()->getParameter('badpixxel_tasking');
        Assert::assertIsArray($config);
        $config = $config["supervisor"];
        Assert::assertIsArray($config);
        Assert::assertIsInt($config['max_workers'] ?? null);

        //====================================================================//
        // Load Workers for Local Supervisor
        /** @var Worker[] $workers */
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
            Assert::assertTrue($manager->isRunning($refreshedWorker->getProcess()));
            Assert::assertTrue($this->doCheckProcessIsAlive($refreshedWorker->getPid()));
        }
    }

    /**
     * Test of Worker Disable Feature
     *
     * @throws Exception
     */
    public function testWorkerIsDisabled(): void
    {
        $manager = $this->getWorkersManager();
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

        $manager->checkSupervisor();
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
            Assert::assertTrue($manager->isRunning($refreshedWorker->getProcess()));
            Assert::assertFalse($refreshedWorker->isEnabled());
            Assert::assertFalse($this->doCheckProcessIsAlive($refreshedWorker->getPid()));
            Assert::assertFalse($worker->isRunning());
        }

        //====================================================================//
        // RESTART ALL WORKERS
        //====================================================================//

        $this->doStopCommand(false);
        $manager->checkSupervisor();
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
        // Execute Test (SF 4 Versions)
        $process = Process::fromShellCommandline($command);
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
    private function doCheckProcessIsAlive(int $pid): bool
    {
        //====================================================================//
        // Check if Process is Running (POSIX compatible, works on Alpine/BusyBox)
        exec("kill -0 ".$pid." 2>/dev/null", result_code: $exitCode);

        return 0 == $exitCode;
    }
}
