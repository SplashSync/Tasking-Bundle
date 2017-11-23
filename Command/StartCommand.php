<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends ContainerAwareCommand
{
    
    private $Tasking;
    private $Timeout;
    
    protected function configure()
    {
        $this
            ->setName('tasking:start')
            ->setDescription('Tasking Service : Start All Supervisors & Workers Process on All Machines')
        ;
        
    }

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        //====================================================================//
        // Init Tasking Service        
        $this->Tasking = $this->getContainer()->get("TaskingService");
        //====================================================================//
        // Init Outputs        
        $this->output = $Output;
        //====================================================================//
        // User Information        
        if ($Output->isVerbose()) {
            $Output->writeln('<info> Start Supervisor & Workers Process on all found Machines. </info>');
        }
        //====================================================================//
        // Request All Active Workers to Start
        $this->SetupAllWorkers(True);
        //====================================================================//
        // Init Outputs        
        $this->Tasking->setOutputInterface($Output);        
        //====================================================================//
        // Check Crontab is Setuped        
        $this->Tasking->CrontabCheck();
        //====================================================================//
        // Check Supervisor Local is Running        
        $this->Tasking->SupervisorCheckIsRunning();
    }

    private function SetupAllWorkers($Enabled = True) 
    {
        //====================================================================//
        // Clear EntityManager
        $this->getContainer()->get('doctrine')->getManager()->clear();
        
        //====================================================================//
        // Load List of All Currently Setuped Workers
        $Workers = $this->getContainer()->get('doctrine')
                ->getRepository('SplashTaskingBundle:Worker')
                ->findAll();
        
        //====================================================================//
        // Set All Actives Workers as Disabled
        foreach ($Workers as $Worker) {
            //====================================================================//
            // Safety Check - Worker doesn't exists        
            if ($Worker == False)  {
                continue;
            }
            //====================================================================//
            // Worker Is Active => Set as Disabled
            $Worker->setEnabled($Enabled);
        }
        
        //====================================================================//
        // Save Changes to Db
        $this->getContainer()->get('doctrine')->getManager()->flush();
    }

}
    