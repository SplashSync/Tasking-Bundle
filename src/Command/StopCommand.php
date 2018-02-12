<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends ContainerAwareCommand
{
    
    private $Tasking;
    private $Timeout;
    
    protected function configure()
    {
        $this
            ->setName('tasking:stop')
            ->setDescription('Tasking Service : Stop All Supervisors & Workers Process on All Machines')
            ->addOption('no-restart', null, InputOption::VALUE_OPTIONAL, 'Do you want to Restart Workers After Stop?', false)
        ;
        parent::configure();
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
            $Output->writeln('<info> Stop Supervisor & Workers Process on all found Machines. </info>');
        }

        //====================================================================//
        // Request All Active Workers to Stop
        $this->SetupAllWorkers(False);

        //====================================================================//
        // Setup TimeOut for this operation
        $this->SetupTimeout();
        
        //====================================================================//
        // Count Total Number of Wrokers
        $Total = count($this->getContainer()->get('doctrine')
                ->getRepository('SplashTaskingBundle:Worker')
                ->findAll());
                
        //====================================================================//
        // Track Workers are Stopped       
        while ( ($Count = $this->CountActiveWorkers()) && !$this->isInTimeout() ) {
            
            //====================================================================//
            // User Information        
            $Output->writeln('<info> Still ' . $Count . ' Actives Workers Process... </info>');

            //====================================================================//
            // Request All Active Workers to Stop
            $this->SetupAllWorkers(False);

            //====================================================================//
            // Pause        
            sleep(1);
            
        } 
        
        if ( $Input->hasParameterOption('--no-restart') ) {
            $Output->writeln('<question>' . $Total . ' Workers now Sleeping... </question>');
            return;
        }
                
        //====================================================================//
        // Request All Active Workers to Stop
        $this->SetupAllWorkers(True);

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
    
    private function CountActiveWorkers() : int
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
        // Count Actives Workers
        $ActivesWorkers = 0;
        foreach ($Workers as $Worker) {
            //====================================================================//
            // Safety Check - Worker doesn't exists        
            if ($Worker == False)  {
                continue;
            }
            //====================================================================//
            // Worker Is Inactive => Nothing to Do
            if ($Worker->getRunning() == False)  {
                continue;
            }
            $ActivesWorkers++;
        }
        
        return $ActivesWorkers;
    }
    
    private function SetupTimeout() 
    {
        $TimeOutDelay = $this->getContainer()->getParameter('splash_tasking')["watchdog_delay"];
        $this->Timeout  = new \DateTime("- " . $TimeOutDelay . " Seconds");
    }
    
    private function isInTimeout() : bool 
    {
        return ( $this->Timeout->getTimestamp() > (new \DateTime)->getTimestamp() );
    }
}
    