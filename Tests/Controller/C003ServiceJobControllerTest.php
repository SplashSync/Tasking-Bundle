<?php

namespace Splash\Tasking\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Output\NullOutput;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Tests\Jobs\TestServiceJob;

class C003ServiceJobControllerTest extends WebTestCase
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
        // Generate a Fake Output
        $this->Output           = new NullOutput();
    }        
    
    
    /**
     * @abstract    Test of A Service Job Execution
     */    
    public function testServiceJob()
    {
        $NbTasks    =   2;
        $WatchDog   =   0;
        
        //====================================================================//
        // Add Job To Queue
        for($i=0 ; $i < $NbTasks ; $i++) {
            
            //====================================================================//
            // Create a New Job
            $Job    =   (new TestServiceJob());
            //====================================================================//
            // Add Job to Queue
            $this->Dispatcher->dispatch("tasking.add" , $Job);
            
        }

        //====================================================================//
        // While Tasks Are Running
        $TaskFound = False;
        $TaskEnded = 0;
        do {
            usleep(500 * 1E3); // 500Ms

            //====================================================================//
            // We Found Our Task Running
            if ($this->TasksRepository->getActiveTasksCount($Job->getToken())) {
                $TaskFound = True;
            }
            
            //====================================================================//
            // We Found Only One Task Running
            $this->assertLessThan(2,$this->TasksRepository->getActiveTasksCount($Job->getToken()));
            
            if ($this->TasksRepository->getActiveTasksCount($Job->getToken()) == 0 ) {
                $TaskEnded ++;
            } else {
                $TaskEnded  =   0;
            }
            
            $WatchDog++;
            
        } while ( ($WatchDog < (2 * $NbTasks + 2 ) ) && ($TaskEnded < 4) );
        
        //====================================================================//
        //Verify All Tasks Are Finished
        $this->assertEquals(0,$this->TasksRepository->getWaitingTasksCount($Job->getToken()));
        
        //====================================================================//
        // Delete Current Token
        $this->TokenRepository->Delete($Job->getToken());
        //====================================================================//
        // Finished Tasks
        sleep(1);
        $this->TasksRepository->Clear(0);
        
        //====================================================================//
        // Check We Found Our Task Running
        $this->assertTrue($TaskFound);
        
        
    }
  
    
}
