<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Tests\Jobs\TestJob;

use Symfony\Component\Process\Process;

class Z001ProcessCloseControllerTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;    
    
    /**
     * @var \Splash\Tasking\Repository\TasksRepository
     */
    private $TasksRepository;
    
    /**
     * @var \Splash\Tasking\Repository\TokenRepository
     */
    private $TokenRepository;

    /**
     * @var \Splash\Tasking\Services\TaskingService
     */
    private $Tasking;    
    
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $Dispatcher;    
    
    
    /**
     * @var string
     */
    private $RandomStr;
    
    /**
     * @var \Symfony\Component\Console\Output\NullOutput
     */
    private $Output;
    
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        //====================================================================//
        // Link to entity manager Services
        $this->_em              = static::$kernel->getContainer()->get('doctrine')->getManager();
        
        //====================================================================//
        // Link to Entity Repository Services
        $this->TasksRepository  = $this->_em->getRepository('SplashTaskingBundle:Task');         
        $this->TokenRepository  = $this->_em->getRepository('SplashTaskingBundle:Token');   
        
        //====================================================================//
        // Link to Tasking manager Services
        $this->Dispatcher       = static::$kernel->getContainer()->get('event_dispatcher');
        
        //====================================================================//
        // Link to Event Dispatecher Services
        $this->Tasking          = static::$kernel->getContainer()->get('TaskingService');

        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr        = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Generate a Fake Output
        $this->Output           = new NullOutput();
    }  
    
    /**
     * @abstract    Test of Task Inputs
     */    
    public function testStopCommand()
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
        $Workers    = $this->_em
                ->getRepository('SplashTaskingBundle:Worker')
                ->findAll();
        
        //====================================================================//
        // Workers List Shall not be Empty at this Step of Tests
        $this->assertNotEmpty($Workers);
        
        
        //====================================================================//
        // Check all Workers are Stopped 
        foreach ($Workers as $Worker) {
            $this->assertInstanceOf( Worker::class , $Worker);
            $this->assertFalse(     $Worker->getRunning());
            $this->assertNotEmpty(  $Worker->getLastSeen());
            $this->assertFalse(     $this->doCheckProcessIsAlive($Worker->getPid()));
        }
        
        $this->_em->clear();
    }
    
    /**
     * @abstract    Execut Stop Console Command
     */    
    public function doStopCommand()
    {
        //====================================================================//
        // Create Sub-Porcess
        $process = new Process("php bin/console tasking:stop -vv");
        //====================================================================//
        // Clean Working Dir
        $WorkingDirectory   =   $process->getWorkingDirectory();
        if (strrpos($WorkingDirectory, "/web") == (strlen($WorkingDirectory) - 4) ){
            $process->setWorkingDirectory(substr($WorkingDirectory, 0, strlen($WorkingDirectory) - 4));
        } 
        else if (strrpos($WorkingDirectory, "/app") == (strlen($WorkingDirectory) - 4) ){
            $process->setWorkingDirectory(substr($WorkingDirectory, 0, strlen($WorkingDirectory) - 4));
        }         
        //====================================================================//
        // Run Shell Command
        $process->mustRun();
    }
    
    public function doCheckProcessIsAlive($Pid) : bool
    {
        //====================================================================//
        // Init Result Array
        $list = array();
        //====================================================================//
        // Execute Seach Command
        exec( "ps " . $Pid  , $list);
        //====================================================================//
        // Check Result
        return (count($list) > 1) ? True : False;
    }
    
}
