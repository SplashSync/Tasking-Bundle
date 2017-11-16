<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class StatusCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('tasking:status')
            ->setDescription('Tasking Service : Check Status of Tasking Services')
        ;
        
    }

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {       
        $Output->writeln('==============================================');
        $Output->writeln('=          Tasking Bundle Status             =');
        $Output->writeln('==============================================');
        $Output->writeln('');

        //====================================================================//
        // Load Worker Repository        
        $Workers = $this->getContainer()
                ->get("doctrine")->getManager()
                ->getRepository('SplashTaskingBundle:Worker')
                ->findAll();

        //====================================================================//
        // List Workers Status       
        $Output->writeln('==============================================');
        $Output->writeln('= Workers : ');
        $Disabled = 0;
        foreach ($Workers as $Worker) {

            //====================================================================//
            // Workers is Disabled       
            if ( !$Worker->getEnabled() ) {
                $Disabled++;
            } 
            //====================================================================//
            // Workers is Enabled       
            $Status = '===> ' . $Worker->getNodeName();
            $Status.= ' (' . $Worker->getPID() . ':' . ( $Worker->getRunning() ? 'Running' : 'Sleeping' ) . ')';
            $Status.= ' : ' . $Worker->getTask();
            $Output->writeln( $Status );
        }
        $Output->writeln( '===> ' . $Disabled . ' Workers are Disabled' );
        $Output->writeln('==============================================');
        
        $Output->writeln('');
        
        //====================================================================//
        // Load Tasks Repository        
        $Repo = $this->getContainer()
                ->get("doctrine")->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        
        $Output->writeln('==============================================');
        $Output->writeln('= Pending Tasks : ');
        
        //====================================================================//
        // create a new progress bar
        $this->progress = new ProgressBar($Output, 100);
        $this->progress->setMessage("Pending Tasks");
        $this->progress->setFormat('[%bar%] %current%/%max% -- <question>%message%</question> ');
        $this->progress->start();        
        
        $Progress = 0;
        while (1) {
            
            $Repo->clear();
            $Status = $Repo->getTasksSummary();
                    
            if ( $Status['Finished'] >= $Status['Total'] ) {
                $Progress = 100;
                $this->progress->setMessage('All Done! Waiting...');
            } else {
                $Progress = (int) 100 * ($Status['Finished'] / $Status['Total']);
                $this->progress->setMessage( ($Status['Finished'] - $Status['Total']) . ' Tasks Pending...');
            }
            $this->progress->setProgress($Progress);        
            sleep(1);            
        }
        
        return;
        
        //====================================================================//
        // Load Tasking Service        
        $Tasking = $this->getContainer()->get("TaskingService");
        
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
    