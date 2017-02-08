<?php

namespace Splash\Tasking\Command;

use DateTime;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends ContainerAwareCommand
{
    //====================================================================//
    // Constants            
    const USLEEP_TIME       = 5 * 1E4;               // Pause Delay When Inactive               => 50Ms
    
    //====================================================================//
    // Global Parameters Storage            
    private $Config;
    
    //====================================================================//
    // Time & Tasks Counters            
    private $WaitUs     =   0;              // Current Pause in Us
    private $WaitSec    =   0;              // Current Pause Total in Sec
    private $TaskTotal  =   0;              // Total of Tasks Treated since Worker Started
    private $EndDate    = Null;             // Script Max End Date
    
    /*
     * @abstract    Worker Service
     * @var         \Splash\Tasking\Services\WorkerService
     */
    private $Worker    = Null;         
    
    protected function execute(InputInterface $input, OutputInterface $Output)
    {
        //====================================================================//
        // Init
        $this->Initialisation($input, $Output);
        
        //====================================================================//
        // Worker Tasks Execution Loop        
        while( !$this->isToKill() ) {
            
            $Start = microtime(True);
            
            //====================================================================//
            // Run Next Normal Tasks  
            if ( $this->Worker->doNextJob(False,$Output) ) {
                $this->TaskTotal++;
                $this->WaitUs       = 0;
                $this->WaitSec      = 0;
                
                //====================================================================//
                // Ensure a Minimal Task Time of 50Ms  
                $uPause             = self::USLEEP_TIME - round( (microtime(True) - $Start) * 1E6);
                if ($uPause > 0) {
                    usleep($uPause);
                } 
                
                continue;
            }

            //====================================================================//
            // Run Next Static Tasks  
            if ( $this->Worker->doNextJob(True,$Output) ) {
                $this->TaskTotal++;
                $this->WaitUs       = 0;
                $this->WaitSec      = 0;
                
                //====================================================================//
                // Ensure a Minimal Task Time of 50Ms  
                $uPause             = self::USLEEP_TIME - round( (microtime(True) - $Start) * 1E6);
                if ($uPause > 0) {
                    usleep($uPause);
                } 
                
                continue;
            }
            
            //====================================================================//
            // Clean All Old Tasks  
            $this->Worker->CleanUp($Output);
            
            //====================================================================//
            // Wait
            $this->Wait($Output);
        }
        
        //====================================================================//
        // Stop Worker        
        $this->Worker->doWorkerHalt($Output);
        
        //====================================================================//
        // User Information        
        if ($Output->isVerbose()) {
            $Output->writeln('<info> End of Tasking Process. Id ' . $input->getArgument('id') . '</info>');
        }
    }

    /**
     *      @abstract    Command Configuration
     */     
    protected function configure()
    {
        $this
            ->setName('tasking:run')
            ->setDescription('Tasking Service : Run a Tasking Process ')
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
    private function Initialisation(InputInterface $input, OutputInterface $output)
    {
        
        //====================================================================//
        // Clear All Remaining Data In Managers
//        $this->getContainer()->get('doctrine')->getManager()->clear();
//        $this->getContainer()->get('doctrine_mongodb')->getManager()->clear();        
//        
        //====================================================================//
        // Setup Php Specific Settings
//        ini_set('display_errors', 1);
//        error_reporting(E_ERROR);
//        
        //====================================================================//
        // Init Parameters     
        $this->Config       =   $this->getContainer()->getParameter('splash_tasking_bundle.workers');
        $this->EndDate      =   new DateTime( $this->Config["max_age"] . " Seconds" );     // Script Max End Date
        //
        //====================================================================//
        // Load Input Parameters        
        $ProcessId = $input->getArgument('id');
        
        //====================================================================//
        // Safety Checks        
        if (!$ProcessId) {
            $output->writeln('<error> You must provide a proccess Id Number. </error>');
            return;
        }
        
        //====================================================================//
        // User Information        
        if ($output->isVerbose()) {
            $output->writeln('<info> Starting New Tasking Process. Id ' . $ProcessId . '</info>');
        }
        
        //====================================================================//
        // Init Worker        
        $this->Worker   = $this->getContainer()->get("Tasking.Worker.Service");
        $this->Worker->doWorkerInit( $ProcessId );
    }
    
    /**
     *      @abstract    Check if Worker Needs To Be Restarted
     */        
    private function isToKill()
    {
        //====================================================================//
        // Check Tasks Counter        
        if ($this->TaskTotal > $this->Config["max_tasks"]) {
            echo "Exit on tasks Counter" . PHP_EOL;
            return True;
        }
        
        //====================================================================//
        // Check Worker Age        
        if ($this->EndDate < new DateTime()) {
            echo "Exit on worker TimeOut" . PHP_EOL;
            return True;
        }
        
        //====================================================================//
        // Check Worker Memory Usage        
        if ( (memory_get_usage(True) / 1048576 ) > $this->Config["max_memory"]) {
            echo "Exit on Worker Memory Usage" . PHP_EOL;
            return True;
        }        

        return False;
        
    }
    
    /**
     *      @abstract    Check if Worker Needs To Be Restarted
     */        
    private function Wait(OutputInterface $output)
    {
        //====================================================================//
        // Wait MicroSeconds
        usleep(self::USLEEP_TIME);
        $this->WaitUs += self::USLEEP_TIME;
        //====================================================================//
        // Wait Seconds Counters        
        if ($this->WaitUs != 1E6 ) {
            return;
        }
        //====================================================================//
        // User Information             
        if ($output->isVerbose() && $this->WaitSec ) {
            $output->write('<comment>.</comment>');
        } else if ($output->isVerbose() ) {
            $output->writeln("");                    
            $output->write('<comment> Tasking Process is waiting...</comment>');                    
        } else {
            $output->write('<comment>.</comment>');                    
        }       
        //====================================================================//
        // Increment Seconds Counter             
        $this->WaitSec++;
        $this->WaitUs     = 0;
    }    
    
}

