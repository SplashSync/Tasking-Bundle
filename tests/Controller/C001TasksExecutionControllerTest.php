<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestJob;

class TasksExecutionControllerTest extends WebTestCase
{
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;    
    
    /**
     * @var \Splash\Tasking\Repository\TaskRepository
     */
    private $TasksRepository;    
    
    /**
     * @var \Splash\Tasking\Repository\TokenRepository
     */
    private $TokenRepository; 
    
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
        $this->WorkerService = static::$kernel->getContainer()->get('TaskingService');
        
        //====================================================================//
        // Link to entity manager Services
        $this->_em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        //====================================================================//
        // Link to Tasks Reprository        
        $this->TasksRepository = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        
        //====================================================================//
        // Link to Token Reprository        
        $this->TokenRepository = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('SplashTaskingBundle:Token');           
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Generate a Fake Output
        $this->Output       =   new NullOutput();
        
        //====================================================================//
        // CleanUp Tasks
        $this->DeleteAllTasks()->DeleteAllTokens();
        
    }  
    
    /**
     * @abstract    Test of a Basic Job Execution
     */    
    public function testBasic()
    {
        //====================================================================//
        // Link to Event Dispatcher
        $Dispatcher = static::$kernel->getContainer()->get('event_dispatcher');
        
        //====================================================================//
        // Create a Simple Test Job
        $Job    =   new TestJob();
        $Job
                ->setInputs(["Delay-Ms" => 100])
                ->setToken($this->RandomStr);
        
        //====================================================================//
        // Add Job to Queue
        $Dispatcher->dispatch("tasking.add" , $Job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);

        //====================================================================//
        // Load a Task
        $this->_em->clear();
        $Task   =   $this->TasksRepository->findOneByJobToken($this->RandomStr);
        
        //====================================================================//
        // Verify Task
        $this->assertInstanceOf( Task::class , $Task);        
        $this->assertFalse(         $Task->getRunning());        
        $this->assertTrue(          $Task->getFinished());   
        $this->assertNotEmpty(      $Task->getOutputs());   
        $this->assertNotEmpty(      $Task->getStartedAt());   
        $this->assertNotEmpty(      $Task->getFinishedAt());   
        $this->assertEquals( 1,     $Task->getTry());   
        
    }

    /**
     * @abstract    Test of Task Errors Management
     * @dataProvider jobsMethodsProvider
     */    
    public function testTaskErrors($Method,$Finished)
    {
        //====================================================================//
        // Link to Event Dispatcher
        $Dispatcher = static::$kernel->getContainer()->get('event_dispatcher');
        
        //====================================================================//
        // Create a Simple Test Job
        $Job    =   new TestJob();
        $Job
                ->setInputs([
                    "Delay-Ms"              => 50,
                    "Error-On-" . $Method   => True
                    ])
                ->setToken($this->RandomStr);
        
        //====================================================================//
        // Add Job to Queue
        $Dispatcher->dispatch("tasking.add" , $Job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);
        
        //====================================================================//
        // Load a Task
        $this->_em->clear();
        $Task   =   $this->TasksRepository->findOneByJobToken($this->RandomStr);
        
        //====================================================================//
        // Verify Task
        $this->assertInstanceOf( Task::class , $Task);        
        $this->assertFalse(                 $Task->getRunning());        
        $this->assertEquals($Finished ,     $Task->getFinished());   
        $this->assertNotEmpty(              $Task->getOutputs());   
        $this->assertNotEmpty(              $Task->getStartedAt());   
        $this->assertNotEmpty(              $Task->getFinishedAt());  
        $this->assertNotEmpty(              $Task->getFaultStr());  
        $this->assertEquals( 1,             $Task->getTry());   
    }
    
    /**
     * @abstract    Test of Task Exceptions Management
     * @dataProvider jobsMethodsProvider
     */    
    public function testTaskExceptions($Method,$Finished)
    {
        //====================================================================//
        // Link to Event Dispatcher
        $Dispatcher = static::$kernel->getContainer()->get('event_dispatcher');
        
        //====================================================================//
        // Create a Simple Test Job
        $Job    =   new TestJob();
        $Job
                ->setInputs([
                    "Delay-Ms"              => 50,
                    "Exception-On-" . $Method   => True
                    ])
                ->setToken($this->RandomStr);
        
        //====================================================================//
        // Add Job to Queue
        $Dispatcher->dispatch("tasking.add" , $Job);

        //====================================================================//
        // Wait Until All Tasks are Completed
        $this->WaitUntilCompleted(2);
        
        //====================================================================//
        // Load a Task
        $this->_em->clear();
        $Task   =   $this->TasksRepository->findOneByJobToken($this->RandomStr);
        
        //====================================================================//
        // Verify Task
        $this->assertInstanceOf( Task::class , $Task);        
        $this->assertFalse(                 $Task->getRunning());        
        $this->assertEquals($Finished ,     $Task->getFinished());   
        $this->assertNotEmpty(              $Task->getOutputs());   
        $this->assertNotEmpty(              $Task->getStartedAt());   
        $this->assertNotEmpty(              $Task->getFinishedAt()); 
        $this->assertNotEmpty(              $Task->getFaultStr());  
        $this->assertEquals( 1,             $Task->getTry());   
    }    

    /**
     * @abstract    Test Wait Until Tasks Buffer is Empty
     */    
    public function testWaitUntilTasksCompleted() {
        
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
        //====================================================================//
        // Test with no Tasks in Buffer
        $this->assertTrue( $this->WorkerService->waitUntilTaskCompleted() );
        $this->assertEquals( 0, $this->TasksRepository->getPendingTasksCount() );        
        
        //====================================================================//
        // Test with a 1 second Tasks in Buffer
        $this->AddTask($this->RandomStr, 1);
        $this->assertEquals( 1, $this->TasksRepository->getPendingTasksCount() );        
        $this->assertTrue( $this->WorkerService->waitUntilTaskCompleted() );
        $this->assertEquals( 0, $this->TasksRepository->getPendingTasksCount() );        
        
        //====================================================================//
        // Test with a 3 second Tasks in Buffer
        $this->AddTask($this->RandomStr, 3);
        $this->assertEquals( 1, $this->TasksRepository->getPendingTasksCount() );        
        $this->assertFalse( $this->WorkerService->waitUntilTaskCompleted(1) );
        $this->assertEquals( 1, $this->TasksRepository->getPendingTasksCount() );        
        $this->assertTrue( $this->WorkerService->waitUntilTaskCompleted() );
        $this->assertEquals( 0, $this->TasksRepository->getPendingTasksCount() );        

        //====================================================================//
        // Test with a 5 x 1 second Tasks in Buffer
        for ( $i=0 ; $i< 5 ; $i++) {
            $this->AddTask($this->RandomStr, 1);
        }        
        $this->assertEquals( 5, $this->TasksRepository->getPendingTasksCount() );        
        $this->assertTrue( $this->WorkerService->waitUntilTaskCompleted(2) );
        $this->assertEquals( 0, $this->TasksRepository->getPendingTasksCount() );        

        //====================================================================//
        // Test with a 12 x 1 second Tasks in Buffer
        for ( $i=0 ; $i< 12 ; $i++) {
            $this->AddTask($this->RandomStr, 1);
        }        
        $this->assertEquals( 12, $this->TasksRepository->getPendingTasksCount() );        
        $this->assertFalse( $this->WorkerService->waitUntilTaskCompleted(1) );
        
    }

    /**
     * @abstract    Add a New Test Simple Task & Run
     */    
    public function AddTask($Token, $Delay = 1)
    {
        //====================================================================//
        // Create a New Test Job
        $Job    =   new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $Job
                ->setInputs(array("Delay-S" => $Delay))
                ->setToken($Token);
        //====================================================================//
        // Save Task
        static::$kernel
                ->getContainer()
                ->get('event_dispatcher')
                ->dispatch("tasking.add", $Job);
        
        return $Job;
    }    
    
    /**
     * @abstract    Delete All Tasks In Db
     */    
    public function DeleteAllTasks()
    {
        $Tasks = $this->TasksRepository->findAll();
        foreach ($Tasks as $Task) {
            $this->_em->remove($Task);
            $this->_em->flush();            
        }
        
        $this->assertEmpty($this->TasksRepository->findAll());
        
        return $this;
    }    
    
    /**
     * @abstract    Delete All Tokens In Db
     */    
    public function DeleteAllTokens()
    {
        $Tokens = $this->TokenRepository->findAll();
        foreach ($Tokens as $Token) {
            $this->_em->remove($Token);
            $this->_em->flush();            
        }
        
        $this->assertEmpty($this->TokenRepository->findAll());
        
        return $this;
    }    
    
    /**
     * @abstract    Return List of Jobs Methods to Test for Exception & Errors
     */    
    public function jobsMethodsProvider()
    {
        return array(
            array("Validate"    , False),
            array("Prepare"     , False),
            array("Execute"     , False),
            array("Finalize"    , True),
            array("Close"       , True),
        );
        
    }    
    
    /**
     * @abstract    Wait Until Tasks Queue Completed 
     */    
    public function WaitUntilCompleted($Limit)
    {    
        //====================================================================//
        // Wait Unit get this Task Executed
        $WatchDog   = 0;
        $Queue      = 0;
        do {
            usleep(500 * 1E3);  // 500Ms
            $WatchDog++;
            
            $this->_em->clear();
            $Queue = $this->TasksRepository->getWaitingTasksCount();
            $Queue+= $this->TasksRepository->getActiveTasksCount();
            
        } while ( ($WatchDog < (2 * $Limit) ) && ($Queue) );
                
    }
    
}
