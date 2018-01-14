<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Tests\Jobs\TestJob;

use Symfony\Component\Process\Process;

class B002ProcessControllerTest extends WebTestCase
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
     * @abstract    Test of Linux Crontab management
     */    
    public function testCronTab()
    {
        //====================================================================//
        // CHECK if Crontab Management is Active
        //====================================================================//
        $Config =   static::$kernel->getContainer()->getParameter('splash_tasking');
        if ( !$Config["server"]["force_crontab"] ) {
            $this->assertNotEmpty($this->Tasking->CrontabCheck());
            $this->assertTrue($this->Tasking->SupervisorCheckIsRunning());
            sleep(3);
            return;
        }
        
        //====================================================================//
        // DELETE Crontab Configuration
        //====================================================================//
        
        exec('crontab -r > /dev/null 2>&1 &');
        
        //====================================================================//
        // CHECK CRONTAB CONFIG
        //====================================================================//

        $this->Tasking->CrontabCheck();
        
        //====================================================================//
        // VERIFY ALL PROCESS ARE STOPPED
        //====================================================================//

        //====================================================================//
        // Read Current Cron Tab Configuration
        $CronTab = [];
        exec("crontab -l", $CronTab);
        $this->assertEquals(1 , count($CronTab));
        $this->assertInternalType("string" , array_shift($CronTab));
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
     * @abstract    Test of Tasking Worker Process Aativation
     */    
    public function testSupervisorIsRunning()
    {
        //====================================================================//
        // REQUEST START OF SUPERVISOR
        //====================================================================//      
        
        $this->Tasking->SupervisorCheckIsRunning();
        sleep(3);
        
        //====================================================================//
        // CHECK SUPERVISOR is RUNNING
        //====================================================================//      
        
        $this->_em->clear();
        
        $Supervisor     =   $this->Tasking->SupervisorIdentify();
        $this->assertInstanceOf( Worker::class , $Supervisor);
        $this->assertTrue( $Supervisor->getRunning() );

        //====================================================================//
        // CHECK EXPECTED WORKERS are RUNNING
        //====================================================================//      
        
        $Config =   static::$kernel->getContainer()->getParameter('splash_tasking') ["supervisor"];
        
        //====================================================================//
        // Load Workers for Local Supervisor
        $Workers    = $this->_em
                ->getRepository('SplashTaskingBundle:Worker')
                ->findBy(array(
                    "nodeName"  => $Supervisor->getNodeName(),
                    "running"   => 1
                ));
      
        //====================================================================//
        // Verify Workers Count
        $this->assertEquals(
                $Config['max_workers'] + 1,
                count($Workers)
                );
        
        //====================================================================//
        // Verify all Workers are Alive
        foreach ($Workers as $Worker) {
            
            $RefreshedWorker = $this->_em
                ->getRepository('SplashTaskingBundle:Worker')
                ->find($Worker->getId());
 
            $this->_em->refresh($RefreshedWorker);
            $this->assertInstanceOf( Worker::class , $RefreshedWorker);
            $this->assertTrue(     $RefreshedWorker->getRunning());
            $this->assertNotEmpty(  $RefreshedWorker->getLastSeen());
            $this->assertTrue(     $this->Tasking->WorkerCheckIsRunning($RefreshedWorker->getProcess()));
            $this->assertTrue(     $this->doCheckProcessIsAlive($RefreshedWorker->getPid()));
            
        }
        
    }    

    /**
     * @abstract    Test of Worker Disable Feature
     */    
    public function testWorkerIsDisabled()
    {
        $Config =   static::$kernel->getContainer()->getParameter('splash_tasking');
      
        //====================================================================//
        // DISABLE & STOP ALL WORKERS
        //====================================================================//      
        
        $this->doStopCommand(True);
        
        //====================================================================//
        // Save to database
        $this->_em->clear();

        //====================================================================//
        // RESTART ALL WORKERS
        //====================================================================//      
        
        $this->Tasking->SupervisorCheckIsRunning();
        sleep(2);
        
        //====================================================================//
        // VERIFY ALL WORKERS ARE OFF
        //====================================================================//      
        
        //====================================================================//
        // Load Local Supervisor
        $this->_em->clear();
        $Supervisor     =   $this->Tasking->SupervisorIdentify();
        
        //====================================================================//
        // Load Workers for Local Supervisor
        $Workers    = $this->_em
                ->getRepository('SplashTaskingBundle:Worker')
                ->findByNodeName($Supervisor->getNodeName());
        
        //====================================================================//
        // Check all Workers
        foreach ($Workers as $Worker) {
            
            $RefreshedWorker = $this->_em
                ->getRepository('SplashTaskingBundle:Worker')
                ->find($Worker->getId());
                        
            $this->_em->refresh($RefreshedWorker);
            $this->assertInstanceOf( Worker::class , $RefreshedWorker);
            $this->assertTrue(      $this->Tasking->WorkerCheckIsRunning($RefreshedWorker->getProcess()));

            $this->assertFalse(     $RefreshedWorker->getEnabled());                
            $this->assertFalse(     $this->doCheckProcessIsAlive($RefreshedWorker->getPID()));
            $this->assertFalse(     $Worker->getRunning());                
        }        
        
        //====================================================================//
        // RESTART ALL WORKERS
        //====================================================================//      
        
        $this->doStopCommand();
        $this->Tasking->SupervisorCheckIsRunning();
        sleep(2);
        
    }    
    
    
    /**
     * @abstract    Execut Stop Console Command
     */    
    public function doStopCommand($NoTestart = False)
    {
        //====================================================================//
        // Create Sub-Porcess
        $process = new Process("php bin/console tasking:stop --env=test -vv" . ($NoTestart? " --no-restart" : Null) );
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
