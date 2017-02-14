<?php

namespace Splash\Tasking\EventListener;

use Monolog\Logger;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\AbstractJob;


use Symfony\Component\DependencyInjection\ContainerInterface as Container; 

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */

class TaskingEventListener {

    private $tasking;
//    private $logger;


    public function __construct(Container $container) {
        $this->tasking      = $container->get("TaskingService");
//        $this->logger       = $logger;
    }

    public function onSecurityInteractiveLogin() {
        //====================================================================//
        // Ensure Supervisor is Running
        $this->tasking->SupervisorCheckIsRunning();
        return;
    }

    /**
     *      @abstract    Add a New Task on Scheduler 
     * 
     *      @param AbstractJob     $Job
     * 
     *      @return bool 
     */    
    public function onAddAction($Job) { 
        
        //====================================================================//
        // Validate Job
        if( !$this->Validate($Job) ) {
            return False;
        }
        
        //====================================================================//
        // Prepare Task From Job Class
        if ( !($Task = $this->Prepare($Job)) ) {
            return False;
        }
        
        //====================================================================//
        // Add Task To Queue
        $this->tasking->TaskInsert($Task);  
        
        //====================================================================//
        // Check Crontab is Setuped        
        $this->tasking->CrontabCheck();       
        
        return True;
    }      
    
    /**
     *      @abstract    Add a New Task on Scheduler but DO NOT RUN SUPERVISOR
     * 
     *      @param AbstractJob     $Job
     * 
     *      @return bool 
     */    
    public function onInsertAction($Job) { 
        
        //====================================================================//
        // Validate Job
        if( !$this->Validate($Job) ) {
            return False;
        }
        //====================================================================//
        // Prepare Task From Job Class
        if ( !($Task = $this->Prepare($Job)) ) {
            return False;
        }
        //====================================================================//
        // Add Task To Queue
        $this->tasking->TaskInsert($Task);            
        
        return True;
    }     
    
    /**
     *  @abstract    Verify given taask before being added to scheduler 
     * 
     *  @param mixed    $Job        An Object Extending Base Job Object
     * 
     *  @return bool 
     */    
    public function Validate($Job) { 
        //====================================================================//
        // Job Class and Action are not empty
        if ( empty(get_class($Job)) || !method_exists($Job, "getAction") || empty($Job->getAction()) ) {
            return False;
        } 
        //====================================================================//
        // Job Class is SubClass of Base Job Class
        if ( !is_a($Job, "Splash\Tasking\Model\AbstractJob")) {
            return False;
        }             
        //====================================================================//
        // Job Action Method Exists
        if ( !method_exists($Job , $Job->getAction()) ) {
            return False;
        }    
        //====================================================================//
        // Job Priority is Valid
        if ( empty($Job->getPriority()) || !is_integer($Job->getPriority()) ) {
            return False;
        }    
        //====================================================================//
        // If defined, Job Inputs must be an Array
        if ( !empty($Job->getInputs()) && !is_array($Job->getInputs())) {
            return False;
        }    
        //====================================================================//
        // If defined, Job Token is a string
        if ( !empty($Job->getToken()) && !is_string($Job->getToken())) {
            return False;
        }         
        //====================================================================//
        // If is a Static Job 
        //====================================================================//
        if ( is_a($Job, "Splash\Tasking\Model\AbstractStaticJob")) {
            if ( empty($Job->getFrequency()) || !is_numeric($Job->getFrequency()) ) {
                return False;
            } 
        } 
        //====================================================================//
        // If is a Batch Job 
        //====================================================================//
        if ( is_a($Job, "Splash\Tasking\Model\AbstractBatchJob")) {
            if ( empty($Job::batchList) || empty($Job::batchAction) ) {
                return False;
            } 
            if ( !method_exists($Job , $Job::batchList) || !method_exists($Job , $Job::batchAction) ) {
                return False;
            } 
        } 
        return True;
    }        

    /**
     *      @abstract    Take Given Job Parameters ans convert it on a Task for Storage
     * 
     *      @param mixed    $Job       User Job Object
     * 
     *      @return bool 
     */    
    public function Prepare($Job) { 
        
        //====================================================================//
        // Create a New Task
        $Task    =   new Task();
        //====================================================================//
        // Setup Task Parameters
        $Task
            ->setName           (get_class($Job) . "::" . $Job->getAction())
            ->setJobClass       ("\\" . get_class($Job))
            ->setJobAction      ($Job->getAction())
            ->setJobInputs      ($Job->__get("inputs"))
            ->setJobPriority    ($Job->getPriority())
            ->setJobToken       ($Job->getToken())
            ->setSettings       ($Job->getSettings())
            ->setJobIndexKey1   ($Job->getIndexKey1())
            ->setJobIndexKey2   ($Job->getIndexKey2());
        
        //====================================================================//
        // If is a Static Job 
        //====================================================================//
        if ( is_a($Job, "Splash\Tasking\Model\AbstractStaticJob")) {
            $Task
                ->setName           ("[S] " . $Task->getName())
                ->setJobIsStatic    (True)
                ->setJobFrequency   ($Job->getFrequency());
        } 
        
        //====================================================================//
        // If is a Static Job 
        //====================================================================//
        if ( is_a($Job, "Splash\Tasking\Model\AbstractBatchJob")) {
            $Task
                ->setName           ("[B] " . $Task->getName());
        }         
        
        //==============================================================================
        // Validate Token Before Task Insert
        //==============================================================================
        $this->tasking->TokenValidate($Task);
        
        return $Task;
    }         
    
}