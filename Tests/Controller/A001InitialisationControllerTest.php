<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Component\Process\Process;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class A001InitialisationControllerTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();
    }        

    /**
     * @abstract    Stop All Supervisor & Worker Process
     */    
    public function testDisplayLogo()
    {
        echo PHP_EOL;
        echo " ______     ______   __         ______     ______     __  __    " . PHP_EOL;
        echo "/\  ___\   /\  == \ /\ \       /\  __ \   /\  ___\   /\ \_\ \   " . PHP_EOL;
        echo "\ \___  \  \ \  _-/ \ \ \____  \ \  __ \  \ \___  \  \ \  __ \  " . PHP_EOL;
        echo " \/\_____\  \ \_\    \ \_____\  \ \_\ \_\  \/\_____\  \ \_\ \_\ " . PHP_EOL;
        echo "  \/_____/   \/_/     \/_____/   \/_/\/_/   \/_____/   \/_/\/_/ " . PHP_EOL;
        echo "                                                                " . PHP_EOL;
        $this->assertTrue(True);
    }
    
    /**
     * @abstract    Stop All Supervisor & Worker Process
     */    
    public function testStopWorkers()
    {
        //====================================================================//
        // Create Process
        $process = new Process("bin/console tasking:stop");
        
        //====================================================================//
        // Clean Working Dir
        $WorkingDirectory   =   $process->getWorkingDirectory();
        if (strrpos($WorkingDirectory, "/app") == (strlen($WorkingDirectory) - 4) ){
            $process->setWorkingDirectory(substr($WorkingDirectory, 0, strlen($WorkingDirectory) - 4));
        }     
        
        //====================================================================//
        // Run Process
        $process->run();
        
        //====================================================================//
        // Fail => Display Process Outputs
        if ( !$process->isSuccessful() ) {
            echo PHP_EOL . "Exeucted : " . $process->getCommandLine();
            echo PHP_EOL . $process->getOutput();
        }
        
        $this->assertTrue($process->isSuccessful());
        
    }      
    
    
    
}
