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
     * @abstract    Render Status or Result
     */    
    public function Render($Status,$Result = Null)
    {
//        fwrite(STDOUT, "\n" . $Status . " ==> " . $Result); 
    }

    
    /**
     * @abstract    Stop All Supervisor & Worker Process
     */    
    public function testStopWorkers()
    {
        $this->Render(__METHOD__);   
        
        //====================================================================//
        // Create Process
        $process = new Process("bin/console tasking:stop --env=test");
        
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
            echo $process->getCommandLine() . PHP_EOL;
            echo $process->getOutput();
        }
        
        $this->assertTrue($process->isSuccessful());
        
    }      
    
    
    
}
