<?php

namespace Splash\Tasking\Tests\Controller;

use Splash\Tasking\Tests\Jobs\TestJob;
use Splash\Tasking\Entity\Task;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\NullOutput;

class A003TasksRepositoryControllerTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;    
    
    /**
     * @var \Splash\Tasking\Repository\TaskRepository
     */
    private $TaskRepository;    
    
    /**
     * @var \Splash\Tasking\Repository\TokenRepository
     */
    private $TokenRepository;  
    
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        self::bootKernel();
        
        //====================================================================//
        // Link to entity manager Services
        $this->_em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        //====================================================================//
        // Link to Tasks Reprository        
        $this->TaskRepository = $this->_em->getRepository('SplashTaskingBundle:Task');
        //====================================================================//
        // Link to Token Reprository        
        $this->TokenRepository = $this->_em->getRepository('SplashTaskingBundle:Token');   
    }        

    /**
     * @abstract    Delete All Tasks
     */    
    public function testDeleteAllTaskss()
    {
        //====================================================================//
        // Delete All Tasks Completed
        $this->TaskRepository->Clean(0);
        //====================================================================//
        // Verify Delete All Tokens
        $this->assertEquals(0, $this->TaskRepository->Clean(0));
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
    }     
    
    /**
     * @abstract    Add Task
     */    
    public function testWaitingTasksCount()
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStrA    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrB    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrC    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create a Task with Token
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrA));        
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrB));        
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrC));        

        //====================================================================//
        // Verify Waiting Tasks
        $this->assertEquals(
                1, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrA)
                );
        $this->assertEquals(
                1, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrB)
                );
        $this->assertEquals(
                1, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrC)
                );
        $this->assertGreaterThan(
                2, 
                $this->TaskRepository->getWaitingTasksCount()
                );
        
    }      

    
    /**
     * @abstract    Add Task
     */    
    public function testActiveTasksCount()
    {
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStrA    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrB    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrC    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create a Task with Token
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrA));        
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrB));        
        $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrC));        

        //====================================================================//
        // Load a Task
        $Task   =   $this->TaskRepository->findOneByJobToken($this->RandomStrA);
        $this->assertInstanceOf( Task::class , $Task);        
        $this->assertFalse($Task->getRunning());        
        $this->assertFalse($Task->getFinished());        

        //====================================================================//
        // Init Active Count Tasks
        $Offset    =   $this->TaskRepository->getActiveTasksCount();
        //====================================================================//
        // Verify Active Count Tasks
        $this->assertEquals(
                0, 
                $this->TaskRepository->getActiveTasksCount($this->RandomStrA)
                );
        
        //====================================================================//
        // Set Task As Running
        $Task->Validate( new NullOutput() , static::$kernel->getContainer());
        $Task->Prepare( new NullOutput() );
        $this->assertFalse($Task->getFinished());        
        $this->assertTrue($Task->getRunning());        
        $this->assertNotEmpty($Task->getStartedAt());        
        $this->assertNotEmpty($Task->getStartedBy());        
        $this->_em->flush();
        
        //====================================================================//
        // Verify Active Count Tasks
        $this->assertEquals(
                1, 
                $this->TaskRepository->getActiveTasksCount($this->RandomStrA)
                );
        $this->assertGreaterThan(
                $Offset, 
                $this->TaskRepository->getActiveTasksCount()
                );
       
    }      
    
    
    /**
     * @abstract    Test Get Next Task Function
     */    
    public function testGetNextTask(){
        
        //====================================================================//
        // Load Tasks Parameters
        $Options = static::$kernel->getContainer()->getParameter("splash_tasking")["tasks"];
        $Options["try_delay"] = $Options["error_delay"] = 10;
        $NoErrorsOptions    = $Options;
        $NoErrorsOptions["error_delay"] = -1;
        $NoRetryOptions     = $Options;
        $NoRetryOptions["try_delay"]    = -1;
        
//        ob_start();
        
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));

        //====================================================================//
        // Create a Task with Token
        $TestJob    =   $this->AddTask($this->RandomStr);
        $this->assertInstanceOf( TestJob::class , $TestJob);     
        //====================================================================//
        // Verify
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $Task       =   $this->TaskRepository->getNextTask($Options,$this->RandomStr,False);
        
        //====================================================================//
        // Create Task Token
        $this->assertTrue($this->TokenRepository->Validate($this->RandomStr));
        //====================================================================//
        // Acquire Token
        $Token  =   $this->TokenRepository->Acquire($this->RandomStr);
        $this->assertNotEmpty($Token);
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($Options,$this->RandomStr,False));

        //====================================================================//
        // Set Task as Started
        $Task->Validate( new NullOutput() , static::$kernel->getContainer());
        $Task->Prepare(new NullOutput());
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));

        
        //====================================================================//
        // Set Task as Completed
        $Task->Close(5);
        $Task->setFinished(True);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Tried but Not Finished
        $Task->Validate( new NullOutput() , static::$kernel->getContainer());
        $Task->Prepare(new NullOutput());
        $Task->Close(5);
        $Task->setFinished(False);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));
        
        //====================================================================//
        // Release Token
        $this->assertTrue($this->TokenRepository->Release($this->RandomStr));
        
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Running but In Timeout
        $Task->Validate( new NullOutput() , static::$kernel->getContainer());
        $Task->Prepare(new NullOutput());
        $Task->setFinished(False);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertInstanceOf( Task::class , $this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Completed
        $Task->Close(0);
        $this->_em->flush(); 
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask($Options,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($Options,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoErrorsOptions,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask($NoRetryOptions,$this->RandomStr,False));
        
    }
    
    /**
     * @abstract    Add a New Test Simple Task & Run
     */    
    public function AddTask($Token)
    {
        //====================================================================//
        // Create a New Test Job
        $Job    =   new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $Job
                ->setInputs(array("Delay-S" => 1))
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
     * @abstract    Add a New Test Simple Task
     */    
    public function InsertTask($Token)
    {
        //====================================================================//
        // Create a New Test Job
        $Job    =   new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $Job
                ->setInputs(array("Delay-S" => 1))
                ->setToken($Token);
        //====================================================================//
        // Save Task
        static::$kernel
                ->getContainer()
                ->get('event_dispatcher')
                ->dispatch("tasking.insert", $Job);
        
        return $Job;
    }    
    
    /**
     * @abstract    Delete All Tasks In Db
     */    
    public function DeleteAllTasks()
    {
        $Tasks = $this->TaskRepository->findAll();
        foreach ($Tasks as $Task) {
            $this->_em->remove($Task);
            $this->_em->flush();            
        }
        
        $this->assertEmpty($this->TaskRepository->findAll());
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
    }  
}
