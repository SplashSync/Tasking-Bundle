<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestJob;

class C002TaskingControllerTest extends WebTestCase
{
    const TEST_DETPH    =   50;
    
    
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
     * @abstract    Create a New Simple Job to Queue
     */    
    public function AddTask($Token)
    {
        //====================================================================//
        // Create a New Job
        $Job    =   (new TestJob())
                ->setToken($Token)
                ;
        //====================================================================//
        // Add Job to Queue
        $this->Dispatcher->dispatch("tasking.add" , $Job);
        
        return $Job;
    }  

    /**
     * @abstract    Create a New Micro Job to Queue
     */    
    public function AddMicroTask($Token,$Delay)
    {
        //====================================================================//
        // Create a New Job
        $Job    =   (new TestJob())
                ->setToken($Token)
                ->setInputs( [ "Delay-Ms" => $Delay ] )
                ;
        //====================================================================//
        // Add Job to Queue
        $this->Dispatcher->dispatch("tasking.add" , $Job);
        
        return $Job;
    } 
    
    /**
     * @abstract    Test of A Simple Long Task List Execution
     */    
    public function testSimpleTask()
    {
     
        $NbTasks    =   2;
        $WatchDog   =   0;
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->AddTask($this->RandomStr);
        }

        //====================================================================//
        // While Tasks Are Running
        $TaskFound = False;
        $TaskEnded = 0;
        do {
            sleep(1);

            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($this->RandomStr)) {
                $TaskFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($this->RandomStr));
            
            if ($this->TasksRepository->getActiveTasksCount($this->RandomStr) == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
        } while ( ($WatchDog < ($NbTasks + 2 ) ) && ($TaskEnded < 2) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($this->RandomStr));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($this->RandomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->TasksRepository->Clear(0);
        
        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($TaskFound);
        
        
    }
  
    
    /**
     * @abstract    Test of Multiple Micro Tasks Execution
     */    
    public function testMicroTask()
    {       
        $NbTasks    =   self::TEST_DETPH;
        $WatchDog   =   0;
        $Delay      =   100;     // 30ms
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->assertInstanceOf(TestJob::class , $this->AddMicroTask($this->RandomStr, $Delay));
        }

        //====================================================================//
        // While Tasks Are Running
        $TaskFound = False;
        $TaskEnded = 0;
        do {
            usleep( ($Delay / 10) * 1E3);

            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($this->RandomStr)) {
                $TaskFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($this->RandomStr));
            
            if ($this->TasksRepository->getActiveTasksCount($this->RandomStr) == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
        } while ( ($WatchDog < ($NbTasks + 2 ) ) && ($TaskEnded < 1000) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($this->RandomStr));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($this->RandomStr);
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->TasksRepository->Clean(0);
        
        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($TaskFound);
        
    }  
    
    /**
     * @abstract    Test of Multiple Micro Tasks Execution
     */    
    public function testMultiMicroTask()
    {
        $NbTasks    =   self::TEST_DETPH;
        $WatchDog   =   0;
        $Delay      =   30;        // 30ms
        $TaskAFound = $TaskBFound = $TaskCFound = False;
        
        //====================================================================//
        // Create a New Set of Micro Tasks
        $this->TokenA   =   base64_encode(rand(1E5, 1E10));
        $this->TokenB   =   base64_encode(rand(1E5, 1E10));
        $this->TokenC   =   base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->assertInstanceOf(TestJob::class , $this->AddMicroTask($this->TokenA, $Delay));
            $this->assertInstanceOf(TestJob::class , $this->AddMicroTask($this->TokenB, $Delay));
            $this->assertInstanceOf(TestJob::class , $this->AddMicroTask($this->TokenC, $Delay));
        }
        
        $this->_em->clear();
        $this->assertGreaterThan(0,$this->TasksRepository->getWaitingTasksCount());
        
        //====================================================================//
        // While Tasks Are Running
        $TaskEnded = 0;
        do {
            usleep( ($Delay / 10) * 1E3);

            $this->_em->Clear();
            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($this->TokenA)) {
                $TaskAFound = True;
            }
            if ($this->TasksRepository->getActiveTasksCount($this->TokenB)) {
                $TaskBFound = True;
            }
            if ($this->TasksRepository->getActiveTasksCount($this->TokenC)) {
                $TaskCFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($this->TokenA));
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($this->TokenB));
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($this->TokenC));
            
            if ($this->TasksRepository->getActiveTasksCount() == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
        } while ( ($WatchDog < ($NbTasks + 2 ) ) && ($TaskEnded < 1000) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($this->TokenA));
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($this->TokenB));
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($this->TokenC));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($this->TokenA);
        $this->TokenRepository->Delete($this->TokenB);
        $this->TokenRepository->Delete($this->TokenC);
        
        //====================================================================//
        // Clean Finished Tasks
        $this->TasksRepository->Clean(0);
        
        //====================================================================//
        // Check We Found Our Tasks Running
//        $this->assertTrue($TaskAFound);
//        $this->assertTrue($TaskBFound);
//        $this->assertTrue($TaskCFound);
    }      
    
}
