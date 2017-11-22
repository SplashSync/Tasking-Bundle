<?php

namespace Splash\Tasking\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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
        //====================================================================//
        // User Information        
        if ($Output->isVerbose()) {
            $this->showHead($Input, $Output);
            $this->showWorkers($Input, $Output);
        }
        
        //====================================================================//
        // Load Tasks Repository        
        $Repo = $this->getContainer()
                ->get("doctrine")->getManager()
                ->getRepository('SplashTaskingBundle:Task');
        
        while (1) {
            //====================================================================//
            // Fetch Tasks Summary        
            $Repo->clear();
            $Status = $Repo->getTasksSummary();

            //====================================================================//
            // Prepare Tasks Status        
            if ( $Status['Finished'] >= $Status['Total'] ) {
                $this->updateProgressBarr($Output, $Status['Finished'], $Status['Total'], 'All Done! Waiting...');
            } else {
                $Message    = ($Status['Total'] - $Status['Finished']) . ' Tasks Pending...';
                $this->updateProgressBarr($Output, $Status['Finished'], $Status['Total'], $Message);
            }
            sleep(1);    
        }
        
        return;           
    }

    
    protected function showHead(InputInterface $Input, OutputInterface $Output)
    {  
        $Output->writeln('==============================================');
        $Output->writeln('=          Tasking Bundle Status             =');
        $Output->writeln('==============================================');
        $Output->writeln('');
    }   

    protected function showWorkers(InputInterface $Input, OutputInterface $Output)
    {  
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
    }   
    
    private function updateProgressBarr( OutputInterface $Output, $Pending, $Total, $Status) 
    {
        //====================================================================//
        // delete current progress bar
        if (isset($this->progress) ) {
            $this->progress->clear();
        }
        //====================================================================//
        // create a new progress bar
        $this->progress = new ProgressBar($Output, $Total);
        $this->progress->setBarCharacter('<fg=cyan>=</>');
        $this->progress->setProgressCharacter('<fg=red>|</>');
        $this->progress->setFormat('= Pending Tasks : [%bar%] %current%/%max% -- <question>%message%</question> ');
        $this->progress->start();        
        $this->progress->setMessage($Status);
        $this->progress->setProgress($Pending);        
    }
}
    