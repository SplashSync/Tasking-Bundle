<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends ContainerAwareCommand
{
    
    private $tasks;
    
    protected function configure()
    {
        $this
            ->setName('tasking:stop')
            ->setDescription('Tasking Service : Stop All Tasks Process ')
        ;
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //====================================================================//
        // Init Tasking Service        
        $this->tasks = $this->getContainer()->get("TaskingService");
        //====================================================================//
        // Init Outputs        
        $this->output = $output;

        //====================================================================//
        // User Information        
        if ($output->isVerbose()) {
            $output->writeln('<info> Stop Worker Supervision Process & All its Workers. </info>');
        }

        //====================================================================//
        // Load Current Server Infos
        $System    =   posix_uname();
        //====================================================================//
        // Load Current Service Worker by Machine Name
        $Workers = $this->getContainer()
                ->get('doctrine')
                ->getRepository('TaskingBundle:Worker')
                ->findByNodeName( $System["nodename"] );
        
        foreach ($Workers as $Worker) {
            
            //====================================================================//
            // Worker doesn't exists        
            if ($Worker == False)  {
                continue;
            }
            
            //====================================================================//
            // Worker Is Inactive 
            if ($Worker->getRunning() == False)  {
                continue;
            }
            
            //====================================================================//
            // Worker Process Is Inactive 
            if ($Worker->Ping() == False)  {
                continue;
            }
            
            if ($Worker->getPID())  {
                exec("kill " . $Worker->getPID());
            }

        }

        //====================================================================//
        // Get List of All Tasking Process on this Server        
        exec("pgrep " . \Splash\Tasking\Entity\Task::WORKER . " -f",$ProcessList);
        foreach ($ProcessList as $Process) {
            if ( $Process != getmypid()) {
                //====================================================================//
                // Kill Process on this Server        
                exec("kill " . $Process);
            }
        }
        
        //====================================================================//
        // Get List of All Supervisor Process on this Server        
        exec("pgrep " . \Splash\Tasking\Entity\Task::SUPERVISOR . " -f",$ProcessList);
        foreach ($ProcessList as $Process) {
            if ( $Process != getmypid()) {
                //====================================================================//
                // Kill Process on this Server        
                exec("kill " . $Process);
            }
        }
        
        
    }

}
    