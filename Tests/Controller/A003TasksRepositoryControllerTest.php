<?php

namespace Splash\Tasking\Tests\Controller;

use Splash\Tasking\Entity\Task;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
        
        $this->TaskRepository = $this->_em->getRepository('TaskingBundle:Task');         
        $this->TokenRepository = $this->_em->getRepository('TaskingBundle:Token');   
    }        

    /**
     * @abstract    Render Status or Result
     */    
    public function Render($Status,$Result = Null)
    {
//        fwrite(STDOUT, "\n" . $Status . " ==> " . $Result); 
    }

    /**
     * @abstract    Delete All Tasks
     */    
    public function testDeleteAllTaskss()
    {
        $this->Render(__METHOD__);   
        
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
        $this->Render(__METHOD__);   
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStrA    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrB    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrC    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create a Task with Token
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrA));        
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrB));        
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrC));        

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
        $this->Render(__METHOD__);   
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStrA    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrB    = base64_encode(rand(1E5, 1E10));
        $this->RandomStrC    = base64_encode(rand(1E5, 1E10));
        
        //====================================================================//
        // Create a Task with Token
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrA));        
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrB));        
        $this->assertInstanceOf(Task::class , $this->AddTask($this->RandomStrC));        

        //====================================================================//
        // Load a Task
        $Task   =   $this->TaskRepository->findOneByJobToken($this->RandomStrA);
        $this->assertInstanceOf(Task::class , $Task);        
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
        $Task->Init();
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
        $this->Render(__METHOD__);   
        
        //====================================================================//
        // Delete All Tasks
        $this->DeleteAllTasks();
        $this->DeleteAllTokens();
        
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        
        //====================================================================//
        // Generate a Random Token Name
        $this->RandomStr    = base64_encode(rand(1E5, 1E10));

        //====================================================================//
        // Create a Task with Token
        $Task   =   $this->AddTask($this->RandomStr);
        $this->assertInstanceOf(Task::class , $Task);     
        //====================================================================//
        // Verify
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        
        //====================================================================//
        // Create Task Token
        $this->assertTrue($this->TokenRepository->Validate($this->RandomStr));
        //====================================================================//
        // Acquire Token
        $Token  =   $this->TokenRepository->Acquire($this->RandomStr);
        $this->assertNotEmpty($Token);
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));

        //====================================================================//
        // Set Task as Started
        $Task->Init();
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));

        
        //====================================================================//
        // Set Task as Completed
        $Task->Close();
        $Task->setFinished(True);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Tried but Not Finished
        $Task->Init();
        $Task->Close();
        $Task->setFinished(False);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
        //====================================================================//
        // Release Token
        $this->assertTrue($this->TokenRepository->Release($this->RandomStr));
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Tried but Not Finished
        $Task->Init();
        $Task->Close();
        $Task->setFinished(False);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Running but In Timeout
        $Task->Init();
        $Task->setFinished(False);        
        $this->_em->flush();   
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertInstanceOf(Task::class , $this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
        //====================================================================//
        // Set Task as Completed
        $Task->Close();
        $Task->setFinished(True);        
        $this->_em->flush(); 
        //====================================================================//
        // Verify
        $this->assertNull($this->TaskRepository->getNextTask(10,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,10,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(10,0,$this->RandomStr,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,Null,False));
        $this->assertNull($this->TaskRepository->getNextTask(0,10,$this->RandomStr,False));
        
    }
    
    /**
     * @abstract    Add a New Dummy Simple Task
     */    
    public function AddTask($Token)
    {
        //====================================================================//
        // Create a New Task
        $Task    =   new Task();
        //====================================================================//
        // Setup Task Parameters
        $Task
                ->setName("Test Task (" . $Token . ")")
                ->setServiceName("TaskingSamplingService")
                ->setJobName("MicroDelayTask")
                ->setJobParameters(array("Delay" => 1E3))
                ->setJobPriority(Task::DO_LOW)
                ->setJobToken($Token);
        
        //====================================================================//
        // Save Task
        $this->_em->persist($Task);
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
