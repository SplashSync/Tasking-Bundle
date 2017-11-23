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
            // Prepare Tasking Status        
            $Message    = $this->getTasksStatusStr($Status['Finished'], $Status['Total']);
            $Message   .= $this->getWorkersStatusStr();
            //====================================================================//
            // Update Tasking Progress Bar        
            $this->updateProgressBarr($Output, $Status['Finished'], $Status['Total'], $Message);
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
                $WorkerStatus = 'Disabled';
            } else if ( $Worker->getRunning() ) {
                $WorkerStatus = 'Running';
            } else {
                $WorkerStatus = 'Sleeping';
            }
            //====================================================================//
            // Workers is Enabled       
            $Status = '===> ' . $Worker->getNodeName();
            $Status.= ' (' . $Worker->getPID() . ':' . $WorkerStatus . ')';
            if ( $Worker->getEnabled() ) {
                $Status.= ' : ' . $Worker->getTask();
            }            
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
        $this->progress->setFormat('= Pending Tasks : [%bar%] %current%/%max% -- %message%');
        $this->progress->start();        
        $this->progress->setMessage($Status);
        $this->progress->setProgress($Pending);        
    }
    
    protected function getTasksStatusStr( $Finished, $Total )
    { 
        if ( $Finished >= $Total ) {
            return ' <info>' . 'All Done! ' . '</info>';
        } else {
            $Message    = ($Total - $Finished) . ' Tasks Pending... ';
            return ' <comment>' . $Message . '</comment>';
        }
    }
        
    protected function getWorkersStatusStr()
    {  
        //====================================================================//
        // Load Worker Repository        
        $Workers = $this->getContainer()
                ->get("doctrine")->getManager()
                ->getRepository('SplashTaskingBundle:Worker');
        $Workers->clear();
        
        //====================================================================//
        // Init Counters       
        $Disabled = 0;
        $Sleeping = 0;
        $Running = 0;
        $Supervisor = 0;

        //====================================================================//
        // Update Workers Counters
        foreach ( $Workers->findAll() as $Worker) {

            //====================================================================//
            // Workers is Supervisor       
            if ( ( $Worker->getProcess() == 0 ) && $Worker->getRunning() ) {
                $Supervisor++;
            } 
            if ( ( $Worker->getProcess() == 0 ) ) {
                continue;
            } 
            
            //====================================================================//
            // Workers is Disabled       
            if ( !$Worker->getEnabled() ) {
                $Disabled++;
            } else if ( $Worker->getRunning() ) {
                $Running++;
            } else {
                $Sleeping++;
            }
        }
        
        if ( $Running < 1 ) {
            return ' <error>No Worker Running!</error>';
        } 
        if ( $Supervisor < 1 ) {
            return ' <error>No Supervisor Running!</error>';
        }         
        $Response = $Running . '/' . ( $Disabled + $Sleeping + $Running ) . ' Workers ';
        $Response.= $Supervisor . ' Supervisors';
        return ' <info>' . $Response . '</info>';
    }  
    
}
    