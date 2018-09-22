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
    
    private $MaxItems   =   10;
    
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
     * @abstract    Test Counting of Waiting Tasks
     */    
    public function testWaitingTasksCount()
    {
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
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
     * @abstract    Test Counting of Actives Tasks
     */    
    public function testActiveTasksCount()
    {
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
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
        $this->StartTask($Task);
        
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
     * @abstract    Test Counting of Pending Tasks (Waiting or Pending)
     */    
    public function testPendingTasksCount()
    {
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStrA    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrB    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrC    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create X Tasks with Token
        for ( $i=0 ; $i< $this->MaxItems ; $i++) {
            $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrA));        
            $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrB));        
            $this->assertInstanceOf( TestJob::class , $this->InsertTask($this->RandomStrC));        
        }

        //====================================================================//
        // Load a Task
        $Task   =   $this->TaskRepository->findOneByJobToken($this->RandomStrA);        
        //====================================================================//
        // Set Task As Running
        $this->StartTask($Task);
        
        //====================================================================//
        // Verify Waiting Tasks
        $this->assertEquals(
                $this->MaxItems -1, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrA)
                );
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrB)
                );
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getWaitingTasksCount($this->RandomStrC)
                );
        //====================================================================//
        // Verify Active Tasks
        $this->assertEquals(
                1, 
                $this->TaskRepository->getActiveTasksCount($this->RandomStrA)
                );
        $this->assertEquals(
                0, 
                $this->TaskRepository->getActiveTasksCount($this->RandomStrB)
                );
        $this->assertEquals(
                0, 
                $this->TaskRepository->getActiveTasksCount($this->RandomStrC)
                );
        
        //====================================================================//
        // Verify Pending Tasks
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getPendingTasksCount($this->RandomStrA)
                );
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getPendingTasksCount($this->RandomStrB)
                );
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getPendingTasksCount($this->RandomStrC)
                );
        
    }
    
    /**
     * @abstract    Test Counting of User Pending Tasks (Waiting or Pending)
     */    
    public function testUserPendingTasksCount()
    {
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
        //====================================================================//
        // Generate a Random Index Key
        $Key =  base64_encode(rand(1E2, 1E4));
        //====================================================================//
        // Create X Tasks with Token
        for ( $i=0 ; $i< $this->MaxItems ; $i++) {
            $this->assertInstanceOf( TestJob::class , $this->InsertTask(Null, $Key ));        
        }

        //====================================================================//
        // Verify Waiting Tasks
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getWaitingTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Active Tasks
        $this->assertEquals(
                0, 
                $this->TaskRepository->getActiveTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Pending Tasks
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getPendingTasksCount(Null,Null,$Key)
            );

        //====================================================================//
        // Load Tasks List
        $Tasks   =   $this->TaskRepository->findByJobIndexKey1($Key);        
        $this->assertEquals( $this->MaxItems, count($Tasks) );
        //====================================================================//
        // Set Task As Running
        $ActiveTasks    =   (int) ($this->MaxItems / 2);
        for ( $i=0 ; $i< $ActiveTasks ; $i++) {
            $this->StartTask($Tasks[$i]);
        }
        
        //====================================================================//
        // Verify Waiting Tasks
        $this->assertEquals(
                $this->MaxItems - $ActiveTasks, 
                $this->TaskRepository->getWaitingTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Active Tasks
        $this->assertEquals(
                $ActiveTasks, 
                $this->TaskRepository->getActiveTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Pending Tasks
        $this->assertEquals(
                $this->MaxItems, 
                $this->TaskRepository->getPendingTasksCount(Null,Null,$Key)
            );

        //====================================================================//
        // Load Tasks List
        $Task   =   $this->TaskRepository->findOneByJobIndexKey1($Key);        
        $this->assertEquals( $this->MaxItems, count($Tasks) );
        //====================================================================//
        // Set Task As Finished
        $this->FinishTask($Task);
        $ActiveTasks--;
        
        //====================================================================//
        // Verify Waiting Tasks
        $this->assertEquals(
                $this->MaxItems - ($ActiveTasks + 1), 
                $this->TaskRepository->getWaitingTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Active Tasks
        $this->assertEquals(
                $ActiveTasks, 
                $this->TaskRepository->getActiveTasksCount(Null,Null,$Key)
                );
        //====================================================================//
        // Verify Pending Tasks
        $this->assertEquals(
                $this->MaxItems - 1, 
                $this->TaskRepository->getPendingTasksCount(Null,Null,$Key)
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
     * @abstract    Insert a New Test Simple Task (Do Not Start Workers)
     * 
     * @param   string  $Token
     * @param   string  $Index1
     * @param   string  $Index2
     * 
     * @return TestJob
     */
    public function InsertTask(string $Token = Null, string $Index1 = Null, string $Index2 = NUll)
    {
        //====================================================================//
        // Generate Token if Needed
        if (is_null($Token)){
            $Token = base64_encode(rand(1E5, 1E10));
        } 
         
        //====================================================================//
        // Create a New Test Job
        $Job    =   new TestJob();
        //====================================================================//
        // Setup Task Parameters
        $Job
                ->setInputs(array("Delay-S" => 2, "random" => base64_encode(rand(1E5, 1E10))))
                ->setToken($Token);
        //====================================================================//
        // Setup Indexes
        if ( !is_null($Index1) ){
            $Job->__set('indexKey1', $Index1);
        } 
        if ( !is_null($Index2) ){
            $Job->__set('indexKey2', $Index2);
        } 
        //====================================================================//
        // Save Task
        static::$kernel
                ->getContainer()
                ->get('event_dispatcher')
                ->dispatch("tasking.insert", $Job);
        
        return $Job;
    }    
    
    /**
     * @abstract    Manually Start a Task
     * @param       Task    $Task
     * @return      Task
     */
    private function StartTask(Task $Task)
    {
        
        //====================================================================//
        // Manually Start Task
        $Task->Validate( new NullOutput() , static::$kernel->getContainer());
        $Task->Prepare( new NullOutput() );
        //====================================================================//
        // Verify Task State
        $this->assertFalse($Task->getFinished());        
        $this->assertTrue($Task->getRunning());        
        $this->assertNotEmpty($Task->getStartedAt());        
        $this->assertNotEmpty($Task->getStartedBy());        
        //====================================================================//
        // Save
        $this->_em->flush();
        
        return $Task;
    }   
    
    /**
     * @abstract    Manually Finish a Task
     * @param       Task    $Task
     * @return      Task
     */
    private function FinishTask(Task $Task)
    {
        
        //====================================================================//
        // Manually Finish Task
        $Task->Close( 0 );
        //====================================================================//
        // Verify Task State
        $this->assertTrue($Task->getFinished());        
        $this->assertFalse($Task->getRunning());        
        $this->assertNotEmpty($Task->getFinishedAt());        
        //====================================================================//
        // Save
        $this->_em->flush();
        
        return $Task;
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
