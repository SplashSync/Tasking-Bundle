<?php

namespace Splash\Tasking\Services;

use Symfony\Component\EventDispatcher\GenericEvent;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;

use UserBundle\Entity\User;

/**
 * Tasks Management Service
 */
class TaskingService 
{

    /*
     *  Doctrine Entity Manager
     * @var \Doctrine\ORM\EntityManager
     */
    public $em;
    
    /*
     *  Symfony Service Container
     */
    private $container;
    
    /*
     *  Tasking Service Configuration Array
     */
    private $Config;
    
    /*
     *  Fault String
     */
    public $fault_str;    

//====================================================================//
//  CONSTRUCTOR
//====================================================================//
    
    /**
     *      @abstract    Class Constructor
     */    
    public function __construct($entityManager, $container) {
        
        //====================================================================//
        // Link to entity manager Service
        $this->em                   =   $entityManager;        
        
        //====================================================================//
        // Link to Session Container
        $this->container            =   $container;           
        
        //====================================================================//
        // Init Parameters        
        $this->Config               =   $container->getParameter('splash_tasking_bundle.tasks');
        return True;
    }    

//====================================================================//
// *******************************************************************//
//  Tokens Short Access
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract    Build Token Key Name from an Array of Parameters
     * 
     *      @param       array    $TokenArray     Token Parameters Given As Array 
     */    
    public function BuildToken($TokenArray = Null) {
        //====================================================================//
        // Build Token Key Name
        return Token::Build($TokenArray);
    }     

    
//====================================================================//
// *******************************************************************//
//  Normal Tasks Management
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract    Add a New Task on Gearman Scheduler 
     * 
     *      @param Task             $Task       Task Object
     * 
     *      @return bool 
     */    
    public function add(Task $Task) { 
        
        //====================================================================//
        // Safety Check
        if ( empty($Task->getServiceName()) || empty($Task->getJobName()) ) {
            return False;
        } 

        //====================================================================//
        // Setup Default Task Parameters if Empty
        //====================================================================//
        
        //====================================================================//
        // Task Name
        if (empty($Task->getName())) {
            $Task->setName( $Task->getJobName() . "@" . $Task->getServiceName() );
        }
        //====================================================================//
        // Verify Task Token
        $Token = $Task->getJobToken();
        if ( is_array($Token) ) {
            //====================================================================//
            // Array Given => Build Token
            $Task->setJobToken( Token::Build($Token) );
        } elseif (empty($Task->getJobToken())) {
            //====================================================================//
            // Array Given => Build Token
            $Task->setJobToken( Token::Build( [ $Task->getServiceName(), $Task->getJobName() ] ) );
        }
        //====================================================================//
        // Task Priority
        if (empty($Task->getJobPriority())) {
            $Task->setJobPriority( Task::DO_NORMAL );
        }        
        
        //==============================================================================
        // Validate Token Before Task Insert
        //==============================================================================
        $ValidToken =   $this->em->getRepository('TaskingBundle:Token')
                ->Validate( $Task->getJobToken() );
        if ( !$ValidToken ) {
            return False;
        } 
        
        //====================================================================//
        // Save New Task
        //====================================================================//

        //====================================================================//
        // Persist New Task to Db
        $this->em->persist($Task);
        $this->em->flush();
        
        //====================================================================//
        // Ensure Workers are Alive
        $this->RunTasks();
        
        return True;
    }        
    
    /**
     *      @abstract    Add a New Task on Gearman Scheduler  
     */    
    public function addTask($serviceName, $jobName, $jobParameters, User $User = Null, $TokenName = "None",  $jobPriority = Task::DO_NORMAL, $Settings = Null ) { 
        
        //====================================================================//
        // Safety Check
        if ( empty($serviceName) || empty($jobName) || empty($jobParameters) ) {
            return False;
        } 
        //====================================================================//
        // Create a New Task
        $Task    =   new Task();
        //====================================================================//
        // Setup Task Parameters
        $Task
                ->setServiceName($serviceName)
                ->setJobName($jobName)
                ->setJobParameters( $jobParameters )
                ->setJobPriority($jobPriority)
                ->setJobToken($TokenName)
                ->setSettings($Settings);
        
        //====================================================================//
        // Setup Task User if Given
        if ( !is_null($User) ) {
            $Task->setUser($User);
        }
        
        return $this->add($Task);
    }        
    
    /**
     *      @abstract    Add a New Task on Gearman Scheduler 
     * 
     *      @param GenericEvent     $Event
     * 
     *      @return bool 
     */    
    public function onAddAction(GenericEvent $Event) { 
        
        //====================================================================//
        // Extract Task From Event
        $Task =     $Event->getSubject();
        //====================================================================//
        // Check is Task Object
        if (is_a($Task, Task::class )) {
            
            //====================================================================//
            // Add Task To Queue
            $this->add($Task);            
            return True;
            
        }
        return False;
    }   
    
    /**
     *      @abstract    Ensure Supervisor Service is Running on Local Machine (Server Node)
     */    
    public function RunTasks() 
    {
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Retrieve Server Local Supervisor
        $Supervisor    = $this->em->getRepository('TaskingBundle:Worker')
                ->findOneBy( array("nodeName" => $System["nodename"], "process" => 0)); 
        //====================================================================//
        // Supervisor Exists & Is Running        
        if ( $Supervisor && $Supervisor->getRunning() )  {
            return True;
        }

        //====================================================================//
        // Execute Shell Command to Supervisor
        //====================================================================//
        return Task::Process(Task::SUPERVISOR,$this->Config["environement"]);
    }
    
//====================================================================//
// *******************************************************************//
// Static Tasks Management
// *******************************************************************//
//====================================================================//    
    
    /**
     *      @abstract   Initialize Static Task Buffer in Database vs Parameters
     */    
    public function InitStaticTasks() {
        
        //====================================================================//
        // Get List of Static Tasks from Parameters
        $Parameters     =   $this->container->getParameter('splash_tasking_bundle.static');
        
        //====================================================================//
        // Get List of Static Tasks via Event Listner
        $Dispatcher =   $this->container->get('event_dispatcher');
        $Event      =   $Dispatcher->dispatch("tasking.static", new GenericEvent($Parameters));
        
        //====================================================================//
        // Get List of Static Tasks in Database
        $Database       =   $this->em->getRepository('TaskingBundle:Task')
                ->getStaticTasks();
        
        //====================================================================//
        // Loop on All Database Tasks to Identify Static Tasks
        $Delete = array();
        foreach($Database as $Task) {
            
            //====================================================================//
            // If Task Not to Run (Doesn't Exists) => To delete
            if ( !$Event->hasArgument( $Task->getName() ) ) {
                $Delete[]  =   $Task;
                continue;
            } 
            
            $StaticTask = $Event->getArgument( $Task->getName() );
            //====================================================================//
            // If Tasks Are Not Similar => To delete & Add
            if ( !$this->CompareStaticTasks($Task,$StaticTask) ) {
                $Delete[]   =   $Task;
                continue;
            }
            
            //====================================================================//
            // Static Task is Ok => Do Nothing
            $Event->setArgument( $Task->getName(), Null );
            
        }
        
        //====================================================================//
        // Loop on Tasks to Delete From Database
        foreach($Delete as $Task) {
            $this->em->remove($Task);
            $this->em->flush();
        }
        
        //====================================================================//
        // Loop on Tasks to Add it On Database
        foreach($Event->getArguments() as $Task) {
            if ($Task) {
                $this->add($Task);
            }
        }
        
        return $this;
    } 
    
//    /**
//     *      @abstract   Clean Static Task Buffer to remove deleted tasks form Database
//     */    
//    public function CleanStaticTasks() {
//        
//        //====================================================================//
//        // Get List of Static Tasks from Parameters
//        $Parameters     =   $this->container->getParameter('splash_tasking_bundle.static');
//        //====================================================================//
//        // Get List of Static Tasks in Database
//        $Database       =   $this->em
//                ->getRepository('TaskingBundle:Task')
//                ->getStaticTasks();
//        //====================================================================//
//        // Loop on All Database Tasks to Identify Static Tasks
//        foreach($Database as $Task) {
//            //====================================================================//
//            // Identify in Defined Tasks
//            if (!$this->IdentifyStaticTasksKey($Parameters,$Task)) {
//                //====================================================================//
//                // If Doesn't Exists, remove form database
//                $this->em->remove($Task);
//                $this->em->flush();
//            } 
//        }
//        
//        return $this;
//    }
    
    /**
     *      @abstract   Identify Static Task in Parameters
     */    
    public function CompareStaticTasks($Task1,$Task2) {

        //====================================================================//
        // Filter by Service Name
        if ( $Task1->getServiceName()   != $Task2->getServiceName() ) {
            return False;
        }
        //====================================================================//
        // Filter by Function Name
        if ( $Task1->getJobName()       != $Task2->getJobName() ) {
            return False;
        }
        //====================================================================//
        // Filter by Token
        if ( $Task1->getJobToken()      != $Task2->getJobToken() ) {
            return False;
        }
        //====================================================================//
        // Filter by Token
        if ( $Task1->getJobFrequency()  != $Task2->getJobFrequency() ) {
            return False;
        }
        
        return True;
    }    
//    
//    /**
//     *      @abstract    Add a New Static Task on Scheduler  
//     */    
//    public function addStaticTask($serviceName, $jobName, $jobParameters, $jobFrequency, $TokenName = "None",  $Name = "Unknown Task Name" ) 
//    { 
//        //====================================================================//
//        // Safety Check
//        if ( empty($serviceName) || empty($jobName) || empty($jobParameters) ) {
//            return False;
//        } 
//        //====================================================================//
//        // Create a New Task
//        $newTask    =   new Task();
//        //====================================================================//
//        // Setup Task Parameters
//        $newTask->setName($Name)
//                ->setServiceName($serviceName)
//                ->setJobName($jobName)
//                ->setJobParameters( $jobParameters )
//                ->setJobPriority(Task::DO_NORMAL)
//                ->setJobIsStatic(True)
//                ->setJobFrequency($jobFrequency)
//                ->setPlannedAt( new \DateTime())
//                ->setJobToken($TokenName);
//        //==============================================================================
//        // Create token if necessary
////        if ( !$this->tokens->findOneByName( $TokenName ) ) {
////            $Token = new Token($TokenName);
////            $this->em->persist($Token);
////        }        
//        //====================================================================//
//        // Persist new Task to Db
//        $this->em->persist($newTask);
//        $this->em->flush();
//        return True;
//    }        
        

    
}