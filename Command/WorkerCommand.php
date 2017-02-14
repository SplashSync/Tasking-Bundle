<?php

namespace Splash\Tasking\Command;

use DateTime;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends ContainerAwareCommand
{
    //====================================================================//
    // Constants            
    const USLEEP_TIME       = 5 * 1E4;               // Pause Delay When Inactive               => 50Ms
    
    //====================================================================//
    // Time & Tasks Counters 
    private $TaskStart  =   0;              // Last Task Startup date in Us
    private $StandByUs  =   0;              // Current Pause in Us
    private $StandBySec =   0;              // Current Pause Total in Sec
    private $TaskTotal  =   0;              // Total of Tasks Treated since Worker Started
    private $EndDate    = Null;             // Script Max End Date
    private $MaxTry     =  10;              // Tasks Max Try Count

    /*
     * @abstract    Worker Process Id Number
     * @var         int
     */
    private $Id    = Null;  

    /*
     * @abstract    Worker Object
     * @var         \Splash\Tasking\Entity\Worker
     */
    private $worker    = Null;  
    
    /*
     * @abstract    Supervisor Service
     * @var         \Splash\Tasking\Services\TaskingService
     */
    private $Tasking    = Null;      
    
    /*
     * @abstract    Current Task Class to Execute
     * @var         \Splash\Tasking\Entity\Task
     */
    private $CurrentTask = Null;
    
    protected function execute(InputInterface $input, OutputInterface $Output)
    {
        //====================================================================//
        // Init
        $this->Initialisation($input, $Output);
        
        //====================================================================//
        // Worker Tasks Execution Loop        
        while( !$this->Tasking->WorkerIsToKill($this->worker, $this->TaskTotal, $this->EndDate) ) {
            
            //====================================================================//
            // Store Task Startup Time  
            $this->TaskStart = microtime(True);
            
            //====================================================================//
            // Run Next Normal Tasks  
            if ( $this->doNextJob($Output, False) ) {
                $this->onJobCompleted();
                continue;
            }

            //====================================================================//
            // Run Next Static Tasks  
            if ( $this->doNextJob($Output, True) ) {
                $this->onJobCompleted();
                continue;
            }
            
            //====================================================================//
            // Refresh Supervisor Worker Status (WatchDog)
            $this->Tasking->WorkerRefresh($this->worker);
            
            //====================================================================//
            // Wait
            $this->onLoopCompleted();
        }
        
        //==============================================================================
        // Set Status as Stopped
        $this->worker->setTask("Stopped");
        $this->Tasking->em->flush();
        
        //====================================================================//
        // Release token Before Exit
        $this->Tasking->TokenRelease();        
        
        //====================================================================//
        // User Information        
        $this->Tasking->Output('End of Tasking Process Id ' . $this->Id, "info");
    }

    /**
     * @abstract    Execute Next Available Tasks until no new task is available
     * 
     * @param bool              $StaticMode     Execute Static Tasks
     * 
     */
    public function doNextJob(OutputInterface $Output, $StaticMode = False) 
    {        
        //====================================================================//
        // Load Next Task To Run with Current Token
        //====================================================================//
        $Token = $this->CurrentTask ? $this->CurrentTask->getJobToken() : Null;
        $this->CurrentTask      = $this->Tasking
                ->TasksFindNext($Token, $StaticMode);

        //====================================================================//
        // No Tasks To Execute 
        if ( is_null($this->CurrentTask) ) {
            //====================================================================//
            // Release Token (Return True only if An Active Token was released)
            return $this->Tasking->TokenRelease();
        }
        
        //====================================================================//
        // Acquire or Verify Token For this Task
        //====================================================================//
        if ( $this->Tasking->TokenAcquire($this->CurrentTask) ) {
              
            //==============================================================================
            // Set Worker Status
            $this->worker->setTask($this->CurrentTask->getName());
            
            //==============================================================================
            // Validate & Prepare User Job Execution
            if ( $this->CurrentTask->validate($Output, $this->getContainer() ) && $this->CurrentTask->Prepare($Output)) {
                //==============================================================================
                // Save Status in Db
                $this->Tasking->em->flush();
                
                //====================================================================//
                // Exectue Task
                //====================================================================//
                $this->CurrentTask->Execute($Output);
                
                //==============================================================================
                // Do Post Execution Actions
                $this->CurrentTask->Close($this->MaxTry);
                
            }
        
        }         
        
        //==============================================================================
        // Save Status in Db
        $this->Tasking->em->flush();
        
        //====================================================================//
        // Exit & Ask for a Next Round
        return True;
    }    
    
    /**
     *      @abstract    Command Configuration
     */     
    protected function configure()
    {
        $this
            ->setName('tasking:worker')
            ->setDescription('Run a Tasking Worker Process ')
            ->addArgument(
                'id',
                InputArgument::OPTIONAL,
                'Process identifier'
            )
        ;
    }
    
    /**
     *      @abstract    Initialize Worker Process
     */        
    private function Initialisation(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Init Supervisor Configuration        
        $this->InitializeConfiguration($Input,$Output);
        //====================================================================//
        // Init Worker        
        $this->InitializeWorker();
    }
    
    /**
     *      @abstract    Initialize Current Worker Process 
     */    
    public function InitializeConfiguration(InputInterface $Input, OutputInterface $Output) {
        //====================================================================//
        // Load Tasking Service        
        $this->Tasking      =   $this->getContainer()->get("TaskingService");
        //====================================================================//
        // Init Outputs        
        $this->Tasking->setOutputInterface($Output);
        //====================================================================//
        // Init Script End DateTime
        $this->EndDate      =   $this->Tasking->WorkerMaxDate();
        //====================================================================//
        // Get Tasks Max Try Count
        $this->MaxTry       =   $this->Tasking->WorkerMaxTry();
        //====================================================================//
        // Load Input Parameters        
        $this->Id = $Input->getArgument('id');
        //====================================================================//
        // Safety Checks        
        if (!$this->Id) {
            $this->Tasking->Output('You must provide a proccess Id Number', "error");
            exit;
        }    
    }     
    
    /**
     *      @abstract    Initialize Current Worker Process 
     */    
    public function InitializeWorker() { 
        //====================================================================//
        // Identify Current Process Worker        
        $this->worker       = $this->Tasking->ProcessIdentify();
        //====================================================================//
        // If Worker Not Found => Search By Supervisor Process Number
        if ( !$this->worker ) {
            //====================================================================//
            // Search Worker By Process Number
            $this->worker   =    $this->Tasking->WorkerIdentify($this->Id);
        }
        //====================================================================//
        // If Worker Doesn't Exists
        if ( !$this->worker ) {
            //====================================================================//
            // Create Worker
            $this->worker = $this->Tasking->WorkerCreate($this->Id);
        } else {
            //====================================================================//
            // Update pID 
            $this->worker->setPID( getmypid() );
            $this->worker->setTask("Boot...");
            $this->Tasking->em->flush();  
        }
        //====================================================================//
        // Refresh Worker
        $this->Tasking->WorkerRefresh($this->worker , True);
    }   
    
    /**
     *      @abstract    Check if Worker Needs To Be Restarted
     */        
    private function onLoopCompleted()
    {
        //====================================================================//
        // Wait MicroSeconds
        usleep(self::USLEEP_TIME);
        $this->StandByUs += self::USLEEP_TIME;
        //====================================================================//
        // Wait Seconds Counters        
        if ($this->StandByUs != 1E6 ) {
            return;
        }
        //====================================================================//
        // User Information             
        if ($this->StandBySec ) {
            $this->Tasking->OutputIsWaiting();
        }       
        //====================================================================//
        // Increment Seconds Counter             
        $this->StandBySec++;
        $this->StandByUs     = 0;
    }    
    
    /**
     *  @abstract    When a task was Executed
     */        
    private function onJobExecuted()
    {


        //==============================================================================
        // End of Task Execution
        $Task->Close();
        
        //====================================================================//
        // Save Changes
        $this->em->flush();
    }    
    
    /**
     *  @abstract    When a task was completed
     *                  => Increment Counters
     *                  => Ensure a minimal Task Time not to overload the proc.
     */        
    private function onJobCompleted()
    {
        
        
        
        
        
        
        //====================================================================//
        // Inc. Counters
        $this->TaskTotal++;
        //====================================================================//
        // Reset StandBy Counters
        $this->StandByUs       = 0;
        $this->StandBySec      = 0;

        //====================================================================//
        // Ensure a Minimal Task Time of 50Ms  
        $uPause = self::USLEEP_TIME - round( (microtime(True) - $this->TaskStart) * 1E6);
        if ($uPause > 0) {
            usleep($uPause);
        } 
    }
    
    
    
}

