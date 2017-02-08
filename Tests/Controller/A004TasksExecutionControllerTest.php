<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;

class TasksExecutionControllerTest extends WebTestCase
{
    /**
     * @var \Splash\Tasking\Services\WorkerService
     */
    private $WorkerService;    
    
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
        // Link to Task Execution Services
        $this->WorkerService = static::$kernel->getContainer()->get('Tasking.Worker.Service');
        
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Generate a Fake Output
        $this->Output       =   new NullOutput();
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
    public function CreateTask()
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
                ->setJobToken($this->RandomStr);
        
        return $Task;
    }    
    
    /**
     * @abstract    Test of Task Inputs
     */    
    public function testTaskInputs()
    {
        $this->Render(__METHOD__);  

        //====================================================================//
        // Create a New Task
        $Task    = $this->CreateTask();
        
        //====================================================================//
        // Setup Task Parameters (Ok Inputs)
        $Task->setJobName("InputsTestTask");
        $Task->setJobParameters(array());
        //====================================================================//
        // Test Task Result
        $this->assertTrue($this->WorkerService->doSingleJob($Task, $this->Output));
        $this->assertNull($Task->getFaultStr());

        //====================================================================//
        // Setup Task Parameters (Nok Inputs)
        $Task->setJobName("InputsTestTask");
        $Task->setJobParameters(Null);
        //====================================================================//
        // Test Task Result
        $this->assertFalse($this->WorkerService->doSingleJob($Task, $this->Output));
        $this->assertNotEmpty($Task->getFaultStr());
        
        //====================================================================//
        // Setup Task Parameters (Nok Inputs)
        $Task->setJobName("InputsTestTask");
        $Task->setJobParameters(new \ArrayObject());
        $Task->setFinished(False);
        //====================================================================//
        // Test Task Result
        $this->assertTrue($this->WorkerService->doSingleJob($Task, $this->Output));
        $this->assertNotEmpty($Task->getFaultStr());
        
    }

    /**
     * @abstract    Test of Task Exceptions & Errors Management
     */    
    public function testTaskErrors()
    {
        $this->Render(__METHOD__);  
        
        //====================================================================//
        // Create a New Task
        $Task    = $this->CreateTask();
        
        //====================================================================//
        // PHP EXCEPTIONS
        //====================================================================//

        //====================================================================//
        // Setup Task Parameters (Error Task)
        $Task->setJobName("ExceptionTask");
        
        //====================================================================//
        // Test Task Result
        $this->assertTrue($this->WorkerService->doSingleJob($Task, $this->Output));
        $this->assertNotEmpty($Task->getFaultStr());
    }  
    
    /**
     * @abstract    Test of Task Exceptions & Errors Management
     */    
    public function testTaskErrorDispatcher()
    {
        $this->Render(__METHOD__);  
        
        $TaskParams = static::$kernel->getContainer()->getParameter('splash_tasking_bundle.tasks');
                
        //====================================================================//
        // Create a New Task
        $Task    = $this->CreateTask();
        
        //====================================================================//
        // PHP EXCEPTIONS
        //====================================================================//

        //====================================================================//
        // Setup Task Parameters (Error Task)
        $Task->setJobName("ExceptionTask");
        
        //====================================================================//
        // Register Listener to Event Dispatcher on Failled Task
        $this->EventReceived = False;        
        static::$kernel->getContainer()->get("event_dispatcher")->addListener('tasking.task.fail', array($this, 'onTaskFailEvent'));           
        

        for( $Count = 0 ; $Count <= $TaskParams["try_count"] ; $Count++ ) {
            //====================================================================//
            // Test Task Result
            $this->assertTrue($this->WorkerService->doSingleJob($Task, $this->Output));
            $this->assertNotEmpty($Task->getFaultStr());
        }
        
        //====================================================================//
        // Verify Event Dispatcher Worked
        $this->assertTrue($this->EventReceived, "Failled Task Event not Received!");
        
        //====================================================================//
        // Verify Task
        $this->assertTrue($Task->getFinished());
    }   
    
    /**
     * @abstract    Test of Task Exceptions & Errors Management
     */    
    public function onTaskFailEvent($Event)
    {
        $this->EventReceived = True;
    }       
    
}
