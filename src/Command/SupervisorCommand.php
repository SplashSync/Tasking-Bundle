<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SupervisorCommand extends ContainerAwareCommand
{
    
    use Traits\LexikMonologBridgeCommand;
    
    //====================================================================//
    // Global Parameters Storage            
    private $Config;
    
    //====================================================================//
    // Time & Workers Counters            
    private $WorkerCount    =   0;          // Number of Worker Started
    private $EndDate        = Null;             // Script Max End Date    
    
    /*
     *  Current System Worker Class
     * @var         \Splash\Tasking\Entity\Worker
     */
    private $supervisor;
    
    /*
     * @abstract    Supervisor Service
     * @var         \Splash\Tasking\Services\TaskingService
     */
    private $Tasking    = Null;          

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Initialize Supervisor Worker
        $this->Initialize($Input, $Output);    
        
        //====================================================================//
        // Run Supervisor Loop
        while(!$this->Tasking->SupervisorIsToKill($this->supervisor, $this->EndDate)) {
        
            //====================================================================//
            // Refresh Status of Each Worker
            for( $Id = 1; $Id <= $this->WorkerCount ; $Id++ ) {
                
                //====================================================================//
                // Check Status of this Worker in THIS Machine Name       
                //====================================================================//
                if ( $this->Tasking->WorkerCheckIsRunning($Id) ) {
                    continue;
                }
                
                //====================================================================//
                // Start This Worker if Not Running
                //====================================================================//
                $this->Tasking->WorkerStartProcess($Id);
                
            }
            //====================================================================//
            // Clean All Old Tasks  
            $this->Tasking->TasksCleanUp();            
            //====================================================================//
            // Refresh Supervisor Worker Status (WatchDog)
            $this->Tasking->WorkerRefresh($this->supervisor);         
            //====================================================================//
            // Wait        
            $this->Tasking->SupervisorDoPause();                  
        }
        
        //==============================================================================
        // Set Status as Stopped
        $this->supervisor->setTask("Stopped");
        $this->supervisor->setRunning(False);
        $this->Tasking->em->flush();
                
        //====================================================================//
        // User Information        
        $this->Tasking->OutputVerbose('End of Supervisor Process', "info");
    }

    protected function configure()
    {
        //====================================================================//
        // Init Supervisor Command        
        $this
            ->setName('tasking:supervisor')
            ->setDescription('Run a Supervisor Worker Process ')
            ->addArgument(
                'workers',
                InputArgument::OPTIONAL,
                'Override Number of Workers to Allocate'
            )
        ;
        
        
    }
    
    protected function Initialize(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Init Supervisor Configuration        
        $this->InitializeConfiguration($Input,$Output);
        
        //====================================================================//
        // Init Supervisor Worker        
        $this->InitializeSupervisorWorker();
        
        //====================================================================//
        // Setup Lexik Logged Compatibility
        $this->overrideLexikMonologBridge();       
        
        //====================================================================//
        // Init Static Tasks List
        $this->Tasking->StaticTasksInit();
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
        $this->EndDate      =   $this->Tasking->SupervisorMaxDate();     
        //====================================================================//
        // Load Input Parameters        
        if (is_null($Input->getArgument('workers')) ) {
            $this->WorkerCount  =   $this->Tasking->SupervisorMaxWorkers();     
        } else {
            $this->WorkerCount  =   $Input->getArgument('workers');
        }
    } 
    
    
    /**
     *      @abstract    Initialize Current Worker Process 
     */    
    public function InitializeSupervisorWorker() { 
        //====================================================================//
        // Identify Current Process Worker        
        $this->supervisor     = $this->Tasking->ProcessIdentify();
        //====================================================================//
        // If Worker Not Found => Search By Supervisor Process Number
        if ( !$this->supervisor ) {
            //====================================================================//
            // Search Worker By Supervisor Process Number
            $this->supervisor   =    $this->Tasking->SupervisorIdentify();
        }
        //====================================================================//
        // If Supervisor Worker Doesn't Exists
        if ( !$this->supervisor ) {
            //====================================================================//
            // Create Supervisor Worker
            $this->supervisor = $this->Tasking->WorkerCreate();
        } else {
            //====================================================================//
            // Update pID 
            $this->supervisor->setPID( getmypid() );
            $this->supervisor->setTask("Supervising");
            $this->Tasking->em->flush();  
        }
        //====================================================================//
        // Refresh Worker
        $this->Tasking->WorkerRefresh($this->supervisor , True);
        //====================================================================//
        // Setup PHP Error Reporting Level        
        error_reporting(E_ERROR);

    }             
}
    