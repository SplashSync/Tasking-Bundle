<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Symfony\Component\EventDispatcher\GenericEvent;

class A005TaskingControllerTest extends WebTestCase
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
        $this->TasksRepository  = $this->_em->getRepository('TaskingBundle:Task');         
        $this->TokenRepository  = $this->_em->getRepository('TaskingBundle:Token');   
        
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
     * @abstract    Render Status or Result
     */    
    public function Render($Status,$Result = Null)
    {
//        fwrite(STDOUT, "\n" . $Status . " ==> " . $Result); 
    }
    
    /**
     * @abstract    Create a New Dummy Simple Task
     */    
    public function CreateTask($Token)
    {
        //====================================================================//
        // Create a New Task
        $Task    =   new Task();
        //====================================================================//
        // Setup Task Parameters
        $Task
                ->setName("Server Test Task")
                ->setServiceName("TaskingSamplingService")
                ->setJobParameters( array() )
                ->setJobPriority(Task::DO_LOW)
                ->setJobToken($Token);
        
        return $Task;
    }  

    /**
     * @abstract    Create a New Dummy Simple Task
     */    
    public function CreateMicroTask($Token,$Delay)
    {
        //====================================================================//
        // Create a New Task
        $Task    =   new Task();
        //====================================================================//
        // Setup Task Parameters
        $Task
                ->setName("Micro Task A (" . $Delay . " MicroSeconds)")
                ->setServiceName("TaskingSamplingService")
                ->setJobName("MicroDelayTask")
                ->setJobParameters(array("Delay" => $Delay))        
                ->setJobPriority(Task::DO_LOW)
                ->setJobToken($Token);
        
        return $Task;
    }    
    
    /**
     * @abstract    Test Inserting an Incomplete Task to Pool
     */    
    public function testIncompleteTask()
    {
        $this->Render(__METHOD__);        
        //====================================================================//
        // Create a New Task
        $Task    = $this->CreateTask(Null);
        
        //====================================================================//
        // Add Task To List
        $this->assertFalse($this->Tasking->add($Task));
        
        //====================================================================//
        // Setup Task Parameters (Ok Inputs)
        $Task->setServiceName(Null);
        $Task->setJobName("DelayTask");
        
        //====================================================================//
        // Add Task To List
        $this->assertFalse($this->Tasking->add($Task));
    }    
  
    
    
    /**
     * @abstract    Test of Task Inputs
     */    
    public function testSupervisourIsRunning()
    {
        $this->Render(__METHOD__);      
        
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        $this->_em->clear();        
        //====================================================================//
        // Retrieve Server Local Supervisor
        $Supervisor    = $this->_em
                ->getRepository('TaskingBundle:Worker')
                ->findOneBy( array("nodeName" => $System["nodename"], "process" => 0)); 
        
        //====================================================================//
        // Start Supervisor if Needed
        if ( !$Supervisor || !$Supervisor->getRunning() ) {
            //====================================================================//
            // Start Supervisor
            $this->assertTrue($this->Tasking->RunTasks(), "Tasking Startup Failled");
            sleep(1);
            $this->_em->clear();
            //====================================================================//
            // Retrieve Server Local Supervisor
            $Supervisor    = $this->_em
                    ->getRepository('TaskingBundle:Worker')
                    ->findOneBy( array("nodeName" => $System["nodename"], "process" => 0)); 
        }
        
        //====================================================================//
        // Check Supervisor
        $this->assertInstanceOf('\Splash\Tasking\Entity\Worker', $Supervisor);
        $this->assertEquals(0,$Supervisor->getProcess());        
        $this->assertTrue($Supervisor->getRunning(), "Supervisor Not Running");        
    }    

    /**
     * @abstract    Test of Task Inputs
     */    
    public function testWorkerIsRunning()
    {
        $this->Render(__METHOD__);    
        
        //====================================================================//
        // Load Current Server Infos
        $System    =   posix_uname();
        //====================================================================//
        // Search for All Workers in THIS Machine Name 
        $Search = array(
            "nodeName"  =>  $System["nodename"],
        );
        $Workers = $this->_em
                        ->getRepository('TaskingBundle:Worker')
                        ->findBy( $Search );        
        
        //====================================================================//
        // Check Workers
        foreach ($Workers as $Worker) {
            $this->assertInstanceOf('\Splash\Tasking\Entity\Worker', $Worker);
        }
        $this->assertGreaterThan(1,count($Workers));        
    }    
    
    /**
     * @abstract    Test of A Simple Long Task List Execution
     */    
    public function testSimpleTask()
    {
        $this->Render(__METHOD__);        
        $NbTasks    =   2;
        $WatchDog   =   0;
        $Delay      =   1;
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create a New Task
        $Task    = $this->CreateTask($this->RandomStr);
        
        //====================================================================//
        // Setup Task Parameters (Ok Inputs)
        $Task->setName("Simple Task (" . $Delay . " Seconds)");        
        $Task->setJobName("DelayTask");
        $Task->setJobParameters(array("Delay" => $Delay));
        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->Dispatcher->dispatch("tasking.add", new GenericEvent(clone $Task) );
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
//        sleep(1);
//        $this->TasksRepository->Clear(0);
        
        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($TaskFound);
        
        
    }
  
    
    /**
     * @abstract    Test of Multiple Micro Tasks Execution
     */    
    public function testMicroTask()
    {
        $this->Render(__METHOD__);        
        $NbTasks    =   100;
        $WatchDog   =   0;
        $Delay      =   3 * 1E4;    // 30ms
        
        //====================================================================//
        // Create a New Micro Task
        $Task  = $this->CreateMicroTask(base64_encode(rand(1E5, 1E10)), $Delay);

        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->assertTrue($this->Tasking->add(clone $Task));
        }

        //====================================================================//
        // While Tasks Are Running
        $TaskFound = False;
        $TaskEnded = 0;
        do {
            usleep($Delay / 10);

            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($Task->getJobToken())) {
                $TaskFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($Task->getJobToken()));
            
            if ($this->TasksRepository->getActiveTasksCount($Task->getJobToken()) == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
        } while ( ($WatchDog < ($NbTasks + 2 ) ) && ($TaskEnded < 1000) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($Task->getJobToken()));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($this->RandomStr);
        //====================================================================//
        // Finished Tasks
//        sleep(1);
//        $this->TasksRepository->Clean(0);
        
        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($TaskFound);
        
        
    }  
    
    /**
     * @abstract    Test of Multiple Micro Tasks Execution
     */    
    public function testMultiMicroTask()
    {
        $this->Render(__METHOD__);        
        $NbTasks    =   100;
        $WatchDog   =   0;
        $Delay      =   1 * 1E4;        // 30ms
        $TaskAFound = $TaskBFound = $TaskCFound = False;
        
        //====================================================================//
        // Create a New Set of Micro Tasks
        $TaskA  = $this->CreateMicroTask(base64_encode(rand(1E5, 1E10)), $Delay);
        $TaskB  = $this->CreateMicroTask(base64_encode(rand(1E5, 1E10)), $Delay);
        $TaskC  = $this->CreateMicroTask(base64_encode(rand(1E5, 1E10)), $Delay);
        
        //====================================================================//
        // Add Task To List
        for($i=0 ; $i < $NbTasks ; $i++) {
            $this->assertTrue($this->Tasking->add(clone $TaskA));
            $this->assertTrue($this->Tasking->add(clone $TaskB));
            $this->assertTrue($this->Tasking->add(clone $TaskC));
        }

        //====================================================================//
        // While Tasks Are Running
        $TaskEnded = 0;
        do {
            usleep($Delay / 10);

            $this->_em->Clear();
            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($TaskA->getJobToken())) {
                $TaskAFound = True;
            }
            if ($this->TasksRepository->getActiveTasksCount($TaskB->getJobToken())) {
                $TaskBFound = True;
            }
            if ($this->TasksRepository->getActiveTasksCount($TaskC->getJobToken())) {
                $TaskCFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($TaskA->getJobToken()));
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($TaskB->getJobToken()));
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($TaskC->getJobToken()));
            
            if ($this->TasksRepository->getActiveTasksCount($TaskC->getJobToken()) == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
        } while ( ($WatchDog < ($NbTasks + 2 ) ) && ($TaskEnded < 1000) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($TaskA->getJobToken()));
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($TaskB->getJobToken()));
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($TaskC->getJobToken()));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($TaskA->getJobToken());
        $this->TokenRepository->Delete($TaskB->getJobToken());
        $this->TokenRepository->Delete($TaskC->getJobToken());
        //====================================================================//
        // Finished Tasks
//        sleep(1);
//        $this->TasksRepository->Clean(0);
        
        //====================================================================//
        // Check We Found Our Tasks Running
        $this->assertTrue($TaskAFound);
        $this->assertTrue($TaskBFound);
        $this->assertTrue($TaskCFound);
        
        
    }      
    
}
