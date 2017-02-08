<?php

namespace Splash\Tasking\Command;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunSupervisorCommand extends ContainerAwareCommand
{
    //====================================================================//
    // Global Parameters Storage            
    private $Config;
    
    //====================================================================//
    // Time & Workers Counters            
    private $WorkerCount    =   0;          // Number of Worker Started
    private $EndDate        = Null;             // Script Max End Date    
    
    
    /*
     * @abstract    Supervisor Service
     * @var         \Splash\Tasking\Services\SupervisorService
     */
    private $Supervisor    = Null;          

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Initialize Supervisor Worker
        $this->Initialize($Input, $Output);    
        
        //====================================================================//
        // Run Supervisor Loop
        while($this->EndDate > new DateTime()) {
        
            //====================================================================//
            // Refresh Status of Local Workers        
            $this->Supervisor->doSupervision($this->WorkerCount,$Output);          
            
            //====================================================================//
            // Wait        
            usleep($this->Config["refresh_delay"]);
            
        }
        //====================================================================//
        // User Information        
        if ($Output->isVerbose()) {
            $Output->writeln('<info> End of Supervisor Process.</info>');
        }
    }

    protected function configure()
    {
        //====================================================================//
        // Init Supervisor Command        
        $this
            ->setName('tasking:runsupervisor')
            ->setDescription('Tasking Service : Run a Tasks Supervisor Process ')
            ->addArgument(
                'workers',
                InputArgument::OPTIONAL,
                'Number of Worker Processes to Allocate'
            )
        ;
        
        
    }
    
    protected function Initialize(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Init Supervisor Service        
        $this->Supervisor   =   $this->getContainer()->get("Tasking.Supervisor.Service");
        //====================================================================//
        // Init Outputs        
        $this->output       =   $Output;
        //====================================================================//
        // Init Parameters        
        $this->Config       =   $this->getContainer()->getParameter('splash_tasking_bundle.supervisor');      
        //====================================================================//
        // Init Script End DateTime
        $this->EndDate      =   new DateTime( "+" . $this->Config['max_age'] . "Seconds" );     
        //====================================================================//
        // Load Input Parameters        
        if (is_null($Input->getArgument('workers')) ) {
            $this->WorkerCount  =   $this->Config['max_workers'];     
        } else {
            $this->WorkerCount  =   $Input->getArgument('workers');
        }
        //====================================================================//
        // User Information        
        if ($Output->isVerbose()) {
            $Output->writeln('<info> Starting Worker Supervision Process. With ' . $this->WorkerCount . ' Workers</info>');
        }
        //====================================================================//
        // Init Worker        
        $this->Supervisor->doSupervisorInit();
        
        //====================================================================//
        // Init Static Tasks List
        $this->getContainer()
                ->get("TaskingService")
                ->InitStaticTasks();
    }      
    
}
    