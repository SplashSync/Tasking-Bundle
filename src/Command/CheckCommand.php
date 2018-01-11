<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends ContainerAwareCommand
{
    
    private $Tasking;
    private $Timeout;
    
    protected function configure()
    {
        $this
            ->setName('tasking:check')
            ->setDescription('Tasking Service : Check Supervisor Process is Running on Current Machines')
        ;
        
    }

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {       
        //====================================================================//
        // Load Tasking Service        
        $Tasking = $this->getContainer()
                ->get("TaskingService");
        //====================================================================//
        // Init Outputs        
        $Tasking->setOutputInterface($Output);        
        //====================================================================//
        // Check Crontab is Setuped        
        $Tasking->CrontabCheck();
        //====================================================================//
        // Check Supervisor Local is Running        
        $Tasking->SupervisorCheckIsRunning();
            
    }

}
    