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
 * Test Closing Processes
 */
class Z001ProcessCloseControllerTest extends AbstractTestController
{
    /**
     * Test of Task Inputs
     */
    public function testStopCommand(): void
    {
        //====================================================================//
        // REQUEST STOP OF ALL PROCESS
        //====================================================================//

        $this->doStopCommand();

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
        /** @var Worker $worker */
        foreach ($workers as $worker) {
//            self::assertInstanceOf(Worker::class, $worker);
            Assert::assertFalse($worker->isRunning());
            Assert::assertNotEmpty($worker->getLastSeen());
            Assert::assertFalse($this->doCheckProcessIsAlive($worker->getPid()));
        }

        $this->entityManager->clear();
    }

    /**
     * Execute Stop Console Command
     */
    public function doStopCommand(): void
    {
        //====================================================================//
        // Create Sub-Process (SF 3.4 Versions)
        $process = new Process("php bin/console tasking:stop -vv");
//        //====================================================================//
//        // Create Sub-Process (SF 4 Versions)
//        $process = Process::fromShellCommandline("php bin/console tasking:stop -vv");
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
     * Check if Process is Alive
     *
     * @param int $pid
     *
     * @return bool
     */
    public function doCheckProcessIsAlive(int $pid) : bool
    {
        //====================================================================//
        // Init Result Array
        $list = array();
        //====================================================================//
        // Execute Seach Command
        exec("ps ".(string) $pid, $list);
        //====================================================================//
        // Check Result
        return (count($list) > 1) ? true : false;
    }
}
