<?php

namespace Splash\Tasking\Services;

use Symfony\Component\EventDispatcher\GenericEvent;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;

use Symfony\Component\Console\Output\OutputInterface;

use ArrayObject, DateTime;

/**
 * Tasks Management Service
 */
class TaskingService 
{
//==============================================================================
//  Constants Definition           
//==============================================================================


    /*
     *  Processing Parameters
     */    
    const CMD_NOHUP         = "/usr/bin/nohup ";                                         // Console Command For NoHup
    const CMD_CONSOLE       = "bin/console ";                                   // Console Command Prefix
    const CMD_SUFIX         = "  < /dev/null > /dev/null 2>&1 &";               // Console Command Suffix
    const WORKER            = "tasking:worker";                                // Worker Start Console Command
    const SUPERVISOR        = "tasking:supervisor";                            // Supervisor Start Console Command    
    const CHECK             = "tasking:check";                                 // Check Start Console Command    
    const CRON              = "* * * * * ";                                     // Crontab Frequency    
    
//==============================================================================
//  Variables Definition           
//==============================================================================

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
     *   Task Repository
     * @var \Splash\Tasking\Repository\TaskRepository
     */
    private $TaskRepository;
    
    /**
     * @var \Splash\Tasking\Repository\WorkerRepository
     */
    private $WorkerRepository; 
    
    /*
     *  Tasking Service Configuration Array
     */
    private $Config;
    
    /*
     *  Symfony Console Output Interface
     */
    private $Output = Null;
    
    /*
     *  Fault String
     */
    public $fault_str;    

    /*
     * @abstract    Current Acquired Token
     * @var         string
     */
    private $CurrentToken = Null;
    
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
        // Link to Tasks Repository
        $this->TaskRepository       =   $entityManager->getRepository('SplashTaskingBundle:Task');
        //====================================================================//
        // Link to Workers Repository
        $this->WorkerRepository     =   $entityManager->getRepository('SplashTaskingBundle:Worker');   
        
        //====================================================================//
        // Init Parameters        
        $this->Config               =   new ArrayObject($container->getParameter('splash_tasking'), ArrayObject::ARRAY_AS_PROPS) ;
        
        return True;
    }   
    
//====================================================================//
// *******************************************************************//
//  Normal Tasks Management
// *******************************************************************//
//====================================================================//
      
    /**
     * Insert Tasks in DataBase
     */
    public function TaskInsert($Task)
    {
        //====================================================================//
        // Persist New Task to Db
        $this->em->persist($Task);
        $this->em->flush();
    }
    
    /**
     * Retrieve Next Available Task from database
     */
    public function TasksFindNext($CurrentToken, $StaticMode = False){
        return  $this->TaskRepository
                ->getNextTask( 
                        $this->Config->tasks,
                        $CurrentToken, 
                        $StaticMode
                        );
    } 
    
    /**
     * Clean Task Buffer to remove old Finished Tasks
     */
    public function TasksCleanUp() 
    {    
        //====================================================================//
        // Delete Old Tasks from Database        
        $CleanCounter = $this->TaskRepository->Clean($this->Config->tasks['max_age']); 
        
        //====================================================================//
        // User Information        
        if ($CleanCounter) {
            $this->OutputVerbose('Cleaned ' . $CleanCounter . ' Tasks' , 'info');
        }         
        
        //====================================================================//
        // Reload Reprository Data
        $this->em->clear();
        
        return $CleanCounter;
    }    
    
//====================================================================//
// *******************************************************************//
// Static Tasks Management
// *******************************************************************//
//====================================================================//    
    
    /**
     *  @abstract   Initialize Static Task Buffer in Database 
     *                  => Tasks are Loaded from Parameters
     *                  => Or by registering Event dispatcher
     */    
    public function StaticTasksInit() {
        
        //====================================================================//
        // Load Event Dispatcher
        $Dispatcher     =   $this->container->get('event_dispatcher');
        //====================================================================//
        // Create A Generic Event 
        $GenericEvent =   new GenericEvent();
        //====================================================================//
        // Fetch List of Static Tasks from Parameters
        $GenericEvent->setArguments($this->Config->static);
        //====================================================================//
        // Complete List of Static Tasks via Event Listner
        $StaticTaskList =   $Dispatcher
                ->dispatch("tasking.static", $GenericEvent)
                ->getArguments();
        //====================================================================//
        // Get List of Static Tasks in Database
        $Database       =   $this->em
                ->getRepository('SplashTaskingBundle:Task')
                ->getStaticTasks();
        
        //====================================================================//
        // Loop on All Database Tasks to Identify Static Tasks
        $Delete = array();
        foreach($Database as $Task) {
            
            //====================================================================//
            // Try to Identify Task in Static Task List
            foreach ($StaticTaskList as $Index => $StaticTask) {
                //====================================================================//
                // If Tasks Are Similar => Delete From List
                if ( $this->StaticTasksCompare($StaticTask, $Task) ) {
                    unset ($StaticTaskList[$Index]);
                    continue;
                }
            }
            
            //====================================================================//
            // Task Not to Run (Doesn't Exists) => Delete from Database
            $this->em->remove($Task);
            $this->em->flush();
        }
       
        //====================================================================//
        // Loop on Tasks to Add it On Database
        foreach($StaticTaskList as $StaticTask) {
            if (class_exists($StaticTask["class"])) {
                $ClassName  =   "\\" . $StaticTask["class"];
                $Job = new $ClassName();
                $Job
                        ->setFrequency  ($StaticTask["frequency"])
                        ->setToken      ($StaticTask["token"])
                        ->setInputs     ($StaticTask["inputs"]);
                
                $Dispatcher->dispatch("tasking.add", $Job);
            }
        }
        
        return $this;
    } 
    
    /**
     *      @abstract   Identify Static Task in Parameters
     */    
    public function StaticTasksCompare(array $StaticTask, Task $Task) {

        //====================================================================//
        // Filter by Class Name
        if ( $StaticTask["class"]       != $Task->getJobClass() ) {
            return False;
        }
        //====================================================================//
        // Filter by Token
        if ( $StaticTask["token"]       != $Task->getJobToken() ) {
            return False;
        }
        //====================================================================//
        // Filter by Frequency
        if ( $StaticTask["frequency"]   != $Task->getJobFrequency() ) {
            return False;
        }
        //====================================================================//
        // Filter by Inputs
        if ( serialize($StaticTask["inputs"])   !== serialize($Task->getJobInputs()) ) {
            return False;
        }
        
        return True;
    }    

//====================================================================//
// *******************************************************************//
//  Tasks Tokens Management
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract    Take Lock on a Specific Token 
     * 
     *      @param       Task       $Task           Task Object
     * 
     */    
    public function TokenAcquire(Task $Task) {

        //==============================================================================
        // Safety Check - If Task Counter is Over => Close Directly This Task
        // This means task was aborded due to a uncatched fatal error
        if ( $Task->getTry() > $this->Config->tasks["try_count"] )    {
            $Task->setFaultStr( "Fatal Error : Task Counter is Over!" , $this->Output);
            return False;
        }    
        
        //==============================================================================
        // Ckeck Token is not Emppty => Skip
        if ( empty($Token = $Task->getJobToken()) ) {
            return True;
        }        
        //==============================================================================
        // Ckeck If we have an Active Token
        if ( !is_null($this->CurrentToken) ) {
            
            //==============================================================================
            // Ckeck If Token is Already Took
            if ( $this->CurrentToken == $Token ) {
                $this->OutputVeryVerbose('Token Already Took! (' . $this->CurrentToken . ')', "comment");
                return True;
            }
            
            //==============================================================================
            // CRITICAL - Release Current Token before Asking for a new one
            $this->TokenRelease();
            $this->Output('Token Not Released before Acquiring a Ne One! (' . $this->CurrentToken . ')', "error");
            return True;
        }
        
        //==============================================================================
        // Try Acquire this Token
        $Token  =   $this->em
                ->getRepository('SplashTaskingBundle:Token')
                ->Acquire( $Task->getJobToken() );
        
        //==============================================================================
        // Ckeck If token is Available
        if ( $Token != False ){
            $this->CurrentToken = $Token->getName();
            $this->OutputVeryVerbose('Token Acquired! (' . $this->CurrentToken . ')', "comment");
            return True;
        }
        
        //==============================================================================
        // Token Rejected
        $this->CurrentToken = Null;
        $this->OutputVeryVerbose('Token Rejected! (' . $Token . ')', "comment");            
        return False;
    }        
    
    /**
     * @abstract    Release Lock on a Specific Token 
     * 
     * @return      bool    Return True only if Current Token was Released
     */    
    public function TokenRelease() {
        //==============================================================================
        // Ckeck If we currently have a token
        if ( is_null($this->CurrentToken) ) {
            return False;
        }
        //==============================================================================
        // Release Token
        $Release = $this->em
                ->getRepository('SplashTaskingBundle:Token')
                ->Release( $this->CurrentToken );
        //==============================================================================
        // Token Released => Clear Current Token
        if ( $Release ){
            $this->CurrentToken         = Null;
            $this->CurrentTokenCount    = 0;
            $this->OutputTokenReleased();
        }        
        return $Release;
    }          
    
    /**
     * @abstract    Validate/Create a Token before insertion of a new Task 
     */    
    public function TokenValidate($Task) {
        
        if ( empty($Task->getJobToken()) ) {
            return True;
        } 
        
        return $this->em
                ->getRepository('SplashTaskingBundle:Token')
                ->Validate( $Task->getJobToken() );
    }          
        
    
//==============================================================================
//      Supervisor Operations
//==============================================================================

    /**
     *  @abstract   Identify Supervisor on this machine 
     *  @return     Worker
     */    
    public function SupervisorIdentify()
    {
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Retrieve Server Local Supervisor
        return  $this->WorkerRepository
                    ->findOneBy( array(
                        "nodeName"  => $System["nodename"], 
                        "process"   => 0
                        ));
    }   
    
    /**
     *  @abstract   Check Supervisor is Running on this machine 
     *              ==> Start a Supervisor Process if needed
     * 
     *  @return     bool
     */    
    public function SupervisorCheckIsRunning() 
    {
        //====================================================================//
        // Load Local Machine Supervisor        
        $Supervisor     = $this->SupervisorIdentify();
        //====================================================================//
        // Supervisor Exists
        if ( $Supervisor )  {
            //====================================================================//
            // Refresh From DataBase
            $this->em->refresh($Supervisor);
            //====================================================================//
            // Supervisor Is Running        
            if ( !$Supervisor->getEnabled() || $Supervisor->getRunning() )  {
                //====================================================================//
                // YES =>    Exit
                $this->OutputVerbose("Supervisor is Running or Disabled", "comment");
                return True;
            }
            
        }
        //====================================================================//
        // NO =>    Start Supervisor Process
        $this->OutputVerbose("Supervisor not Running", "question");
        return $this->ProcessStart(self::SUPERVISOR);
    }    

    /**
     *  @abstract   Check All Available Supervisor are Running on All machines 
     * 
     *  @return     bool
     */    
    public function SupervisorAllCheckAreRunning() 
    {
        //====================================================================//
        // Check Local Machine Supervisor        
        $LocalIsOK     = $this->SupervisorCheckIsRunning();
        //====================================================================//
        // Check if MultiServer Mode is Enabled
        if ( !$this->Config["multiserver"]) {
            return $LocalIsOK;
        }
        //====================================================================//
        // Retrieve List of All Supervisors
        $List = $this->WorkerRepository->findByProcess(0);        
        foreach ($List as $Supervisor) {
            //====================================================================//
            // Refresh From DataBase
            $this->em->refresh($Supervisor);
            //====================================================================//
            // If Supervisor Is NOT Running        
            if ( !$Supervisor->getRunning() )  {
                //====================================================================//
                // Send REST Request to Start                        
                $Url = "http://" . $Supervisor->getIp() . $this->Config["multiserver_path"] . $this->container->get("router")->generate("tasking_start");
                //====================================================================//
                // Send REST Request to Start
                $ch = curl_init($Url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                curl_exec($ch);
                curl_close($ch);
            }
        }
        return True;
    }       
    
    /**
     *  @abstract   Get Max Age for Supervisor (since now) 
     *  @return     datetime
     */    
    public function SupervisorMaxDate() : \DateTime
    {
        $this->OutputVerbose("Tasking :: This Supervisor will die in " . $this->Config->supervisor['max_age'] . " Seconds", "info");
        return new DateTime( "+" . $this->Config->supervisor['max_age'] . "Seconds" );
    }    
    
    /**
     *  @abstract   Get Max Number of Workers for Supervisor (since now) 
     *  @return     int
     */    
    public function SupervisorMaxWorkers() : int
    {
        $this->OutputVerbose("Tasking :: This Supervisor will manage " . $this->Config->supervisor['max_workers'] . " Workers", "info");
        return $this->Config->supervisor['max_workers'];
    }    
    
    /**
     *  @abstract   Do Pause for Supervisor between two Refresh loop 
     *  @return     int
     */    
    public function SupervisorDoPause()
    {
        //====================================================================//
        // Wait        
        usleep(1E3 * $this->Config->supervisor["refresh_delay"]);
    }      
    
    /**
     * @abstract    Check if Supervisor Needs To Be Restarted
     * 
     * @param       Worker      $Worker     Tasking Worker Object
     * @param       int         $TaskCount  Number of Tasks Executed
     * @param       datetime    $EndDate    Expected End Date
     * 
     */    
    public function SupervisorIsToKill(Worker $Worker, $EndDate) : bool { 
        
        //====================================================================//
        // Check Worker Age        
        if ($EndDate < new DateTime()) {
            $this->Output('Exit on Supervisor TimeOut', "question");
            return True;
        }
        
        //====================================================================//
        // Check Worker Memory Usage        
        if ( (memory_get_usage(True) / 1048576 ) > $this->Config->supervisor["max_memory"]) {
            $this->Output('Exit on Supervisor Memory Usage', "question");
            return True;
        }        

        //====================================================================//
        // Check User requested Worker to Stop        
        if ( !$Worker->getEnabled() ) {
            $this->Output('Exit on User Request, Supervisor Now Disabled', "question");
            return True;
        }        
        
        //====================================================================//
        // Check Worker is Alone with this Number
        if ( $this->ProcessExists(self::SUPERVISOR ) > 1) {
            $this->Output('Exit on Duplicate Worker Deteted', "question");
            return True;
        } 
        
        return False;
        
    }
    
//==============================================================================
//      Worker Operations
//==============================================================================

    /**
     *  @abstract   Create a new Worker Object for this Process
     * 
     *  @param      int     $ProcessId      Worker Process Id
     * 
     *  @return     Worker
     */    
    public function WorkerCreate($ProcessId = 0) {
        
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Create Worker Object
        $Worker     =   new Worker();
        //====================================================================//
        // Populate Worker Object
        $Worker->setPID         ( getmypid() );
        $Worker->setProcess     ( $ProcessId );
        $Worker->setNodeName    ( $System["nodename"] );
        $Worker->setNodeIp      ( filter_input(INPUT_SERVER, "SERVER_ADDR") );
        $Worker->setNodeInfos   ( $System["version"] );
        $Worker->setLastSeen    ( new DateTime() );
        //====================================================================//
        // Persist Worker Object to Database
        $this->em->persist( $Worker );
        $this->em->flush();
        return $Worker;
    }
    
    /**
     * @abstract    Refresh Status of a Supervisor Process 
     * 
     * @param       Worker      $Worker     Tasking Worker Object
     */    
    public function WorkerRefresh(Worker &$Worker, bool $Force = False) { 
        
        //====================================================================//
        // Compute Refresh Limit
        $RefreshLimit = new \DateTime("-" . $this->Config->refresh_delay. " Seconds");
        //====================================================================//
        // Update Status if Needed
        if ( ($Worker->getLastSeen()->getTimestamp() < $RefreshLimit->getTimestamp()) || $Force ) {
            //====================================================================//
            // Reload Worker From DB
            $Worker = $this->WorkerIdentify($Worker->getProcess());
            //==============================================================================
            // Refresh Worker Status
            //==============================================================================
            //==============================================================================
            // Set As Running
            $Worker->setRunning( True );
            //==============================================================================
            // Set As Running
            $Worker->setPID( getmypid() );
            //==============================================================================
            // Set Last Seen DateTime to NOW
            $Worker->setLastSeen( new DateTime() );
            //==============================================================================
            // Set Script Execution Time
            set_time_limit($this->Config->watchdog_delay + 2);
            //==============================================================================
            // Set Status as Waiting
            $Worker->setTask("Waiting...");
            //==============================================================================
            // Flush Database
            $this->em->flush();    
            //====================================================================//
            // Output Refresh Sign
            $this->OutputRefreshed();
        }        
            
        return $Worker;
    }       
    
    /**
     *  @abstract   Idnetify Supervisor on this machine 
     *  @param      int $ProcessId Worker Process Id
     *  @return     Worker
     */    
    public function WorkerIdentify($ProcessId)
    {
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Clear Cache of EntityManager
        $this->em->clear();
        //====================================================================//
        // Retrieve Server Local Supervisor
        return  $this->WorkerRepository
                    ->findOneBy( array(
                        "nodeName"  => $System["nodename"], 
                        "process"   => $ProcessId
                        ));
    }   
    
    /**
     *      @abstract    Verify a Worker Process is running
     * 
     *      @param       int        $Process         Worker Local Id
     */    
    public function WorkerCheckIsRunning($Process) {
        //====================================================================//
        // Load Local Machine Worker        
        $Worker     = $this->WorkerIdentify($Process);
        //====================================================================//
        // Worker Found
        if ( $Worker )  {
            //====================================================================//
            // Refresh From DataBase
            $this->em->refresh($Worker);
        }
        //====================================================================//
        // Worker Found & Running
        if ( $Worker && $Worker->getRunning() )  {
            return True;
        //====================================================================//
        // Worker Found & Running
        } elseif ( !$Worker )  {
            $this->OutputVeryVerbose("Workers Process " . $Process . " Doesn't Exists", "question");
        //====================================================================//
        // Worker Is Disabled        
        } elseif ( !$Worker->getEnabled() ) {
            $this->OutputVeryVerbose("Workers Process " . $Process . " is Disabled", "info");
            return True;
        //====================================================================//
        // Worker Is Inactive        
        } else {
            $this->OutputVeryVerbose("Workers Process " . $Process . " is Inactive", "info");
        }             
        //====================================================================//
        // Worker Not Alive
        return False; 
    }          
    
    /**
     *  @abstract   Start a Worker Process on Local Machine (Server Node)
     *  @param      int $ProcessId Worker Process Id
     *  @return     bool
     */    
    public function WorkerStartProcess($ProcessId) 
    {
        $this->ProcessStart(self::WORKER . " " . $ProcessId);
    }    
    
    /**
     *  @abstract   Get Max Age for Worker (since now) 
     *  @return     datetime
     */    
    public function WorkerMaxDate()
    {
        $this->OutputVerbose("Tasking :: This Worker will die in " . $this->Config->workers['max_age'] . " Seconds", "info");
        return new DateTime( "+" . $this->Config->workers['max_age'] . "Seconds" );
    }    
        
    /**
     *  @abstract   Get Max Try Count for Tasks 
     *  @return     int
     */    
    public function WorkerMaxTry()
    {
        return $this->Config->tasks['try_count'];
    }  
    
    /**
     * @abstract    Check if Worker Needs To Be Restarted
     * 
     * @param       Worker      $Worker     Tasking Worker Object
     * @param       int         $TaskCount  Number of Tasks Executed
     * @param       datetime    $EndDate    Expected End Date
     * 
     */    
    public function WorkerIsToKill(Worker $Worker, $TaskCount, $EndDate) { 
        
        //====================================================================//
        // Check Tasks Counter        
        if ($TaskCount > $this->Config->workers["max_tasks"]) {
            $this->Output('Exit on Worker Tasks Counter (' . $TaskCount . ")", "question");
            return True;
        }
        
        //====================================================================//
        // Check Worker Age        
        if ($EndDate < new DateTime()) {
            $this->Output('Exit on Worker TimeOut', "question");
            return True;
        }
        
        //====================================================================//
        // Check Worker Memory Usage        
        if ( (memory_get_usage(True) / 1048576 ) > $this->Config->workers["max_memory"]) {
            $this->Output('Exit on Worker Memory Usage', "question");
            return True;
        }        

        //====================================================================//
        // Check User requested Worker to Stop        
        if ( !$Worker->getEnabled() ) {
            $this->Output('Exit on User Request, Worker Now Disabled', "question");
            return True;
        }
        
        //====================================================================//
        // Check Worker is Alone with this Number
        if ( $this->ProcessExists(self::WORKER . " " . $Worker->getProcess() ) > 1) {
            $this->Output('Exit on Duplicate Worker Deteted', "question");
            return True;
        } 
        
        return False;
        
    }
    
//==============================================================================
//      Process Operations
//==============================================================================
    
    /**
     *      @abstract    Check Crontab Configuration and 
     */    
    public function CrontabCheck() 
    {
        //====================================================================//
        // Check Crontab Management is ACtivated
        if( !$this->Config["server"]["force_crontab"])  {
            return "Crontab Management is Disabled";
        }
        //====================================================================//
        // Compute Working Dir 
        $WorkingDirectory = dirname($this->container->get('kernel')->getRootDir());
        $Env    = $this->container->get('kernel')->getEnvironment();
        //====================================================================//
        // Compute Expected Cron Tab Command
        $Command = self::CRON . " " . $this->Config["server"]["php_version"] . " ";
        $Command.= " " . $WorkingDirectory . "/" . self::CMD_CONSOLE;    
        $Command.= " " . self::CHECK . " --env=" . $Env . " " . self::CMD_SUFIX;        
        //====================================================================//
        // Read Current Cron Tab Configuration
        $CronTab = [];
        exec("crontab -l > /dev/null 2>&1 &", $CronTab);
        $Current = array_shift($CronTab);
        //====================================================================//
        // Update Cron Tab Configuration if Needed
        if ( $Current !==  $Command) {
            exec('echo "' . $Command . '" > crontab.conf');
            exec("crontab crontab.conf");
            return "Crontab Configuration Updated";
        }
        return "Crontab Configuration Already Done";
    }   
    
    /**
     *      @abstract    Start a Process on Local Machine (Server Node)
     */    
    private function ProcessStart($Command, $Environement = Null) 
    {
        //====================================================================//
        // Select Environement
        $Env = $Environement ? $Environement : $this->Config["environement"]; 
        
        //====================================================================//
        // Detect Working Directory
        $WorkingDir = dirname($this->container->get('kernel')->getRootDir());
        
        //====================================================================//
        // Finalize Command
        $RawCmd = self::CMD_NOHUP . $this->Config["server"]["php_version"] . " ";
        $RawCmd.= $WorkingDir . "/" . self::CMD_CONSOLE;
        $RawCmd.= $Command . " --env=" . $Env . self::CMD_SUFIX;     
        
        //====================================================================//
        // Verify This Command Not Already Running
        if ( $this->ProcessExists($Command, $Env) > 0) {
            $this->OutputVerbose("Tasking :: Process already active (" . $RawCmd . ")", "info");
            return True;
        }      
        
        //====================================================================//
        // Execute Command
        exec($RawCmd);
        
        //====================================================================//
        // Wait for Script Startup
        usleep(200 * 1E3); // 100MS
        $this->OutputVeryVerbose("Tasking :: Process Started (" . $RawCmd . ")", "info");
        return True;
    }
    
    
    /**
     *      @abstract    Check if a Similar Process Exists on Local Machine (Server Node)
     */    
    private function ProcessExists($Command, $Environement = Null) 
    {
        //====================================================================//
        // Select Environement
        $Env = $Environement ? $Environement : $this->Config["environement"]; 
                
        //====================================================================//
        // Detect Working Directory
        $WorkingDir = dirname($this->container->get('kernel')->getRootDir());
    
        //====================================================================//
        // Find Command
        $ListCommand = $this->Config["server"]["php_version"]  . " " .  $WorkingDir . "/" . self::CMD_CONSOLE; 
        $ListCommand.= $Command . " --env=" . $Env; 
        
        //====================================================================//
        // Verify This Command Not Already Running
        return (int) exec("pgrep '" . $ListCommand . "' -xfc ",$List);
    }    
    
    /**
     *  @abstract   Identify Current Worker on this machine using it's PID 
     *  @param      int $ProcessId Worker Process Id
     *  @return     Worker
     */    
    public function ProcessIdentify()
    {
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Clear Cache of EntityManager
        $this->em->clear();
        //====================================================================//
        // Retrieve Server Local Supervisor
        return  $this->WorkerRepository
                    ->findOneBy( array(
                        "nodeName"  => $System["nodename"], 
                        "process"   => getmypid()
                        ));
    }     
    
//====================================================================//
// *******************************************************************//
//  Outputs Management
// *******************************************************************//
//====================================================================//
        
    public function setOutputInterface(OutputInterface $Output)
    {
        $this->Output = $Output;
    }   
    
    public function Output($Text, $Type = Null)
    {
        //====================================================================//
        // No Outputs Interface defined => Exit
        if ( !$this->Output ) {
            return;
        }
        //====================================================================//
        // No Output Type Given
        if ( $Type ) {
            $Text = '<' . $Type . '>' . $Text . '</' . $Type . '>';
        }         
        $this->Output->writeln($Text);
    }       
    
    public function OutputVerbose($Text, $Type = Null)
    {
        if ($this->Output && $this->Output->isVerbose()) {
            $this->Output($Text, $Type);
        }         
    }       
    
    public function OutputVeryVerbose($Text, $Type = Null)
    {
        if ($this->Output && $this->Output->isVeryVerbose()) {
            $this->Output($Text, $Type);
        }         
    }       
    
    public function OutputIsWaiting()
    {
        //====================================================================//
        // No Outputs Interface defined => Exit
        if ( !$this->Output ) {
            return;
        }
        //====================================================================//
        // Write waiting Sign
        $this->Output->write(".");
    }       
    
    public function OutputRefreshed()
    {
        //====================================================================//
        // No Outputs Interface defined => Exit
        if ( !$this->Output ) {
            return;
        }
        //====================================================================//
        // Write waiting Sign
        $this->Output->write("|");
    }       
    
    public function OutputTokenReleased()
    {
        //====================================================================//
        // No Outputs Interface defined => Exit
        if ( !$this->Output ) {
            return;
        }
        //====================================================================//
        // Write waiting Sign
        $this->Output->write("X","question");
    }     
}