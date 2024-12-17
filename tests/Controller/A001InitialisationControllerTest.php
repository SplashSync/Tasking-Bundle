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

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Process;

/**
 * Test sequence Initialisation
 */
class A001InitialisationControllerTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * Stop All Supervisor & Worker Process
     */
    public function testDisplayLogo(): void
    {
        //====================================================================//
        // Create Process (SF 4 Versions)
        $process = Process::fromShellCommandline("php bin/console tasking:stop --no-restart");
        //====================================================================//
        // Clean Working Dir
        $workingDirectory = (string) $process->getWorkingDirectory();
        if (strrpos($workingDirectory, "/app") == (strlen($workingDirectory) - 4)) {
            $process->setWorkingDirectory(substr($workingDirectory, 0, strlen($workingDirectory) - 4));
        }

        //====================================================================//
        // Run Process
        $process->run();

        //====================================================================//
        // Fail => Display Process Outputs
        if (!$process->isSuccessful()) {
            echo PHP_EOL."Executed : ".$process->getCommandLine();
            echo PHP_EOL.$process->getOutput();
        }

        Assert::assertTrue($process->isSuccessful());
    }
}
