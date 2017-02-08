<?php

namespace Splash\Tasking\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Process\Process;

/**
 * System Gearman Task
 * 
 * @ORM\Entity(repositoryClass="Splash\Tasking\Repository\TaskRepository")
 * @ORM\Table(name="system__tasks")
 * @ORM\HasLifecycleCallbacks
 * 
 */
class Task
{
    
//==============================================================================
//  Constants Definition           
//==============================================================================

    /*
     *  Tasks Priority
     */    
    const DO_NORMAL         = 5;
    const DO_LOW            = 0;
    const DO_HIGH           = 10;
    
    /*
     *  Worker Parameters
     */    
    // Worker Parameters
    const CMD_PHP           = "php ";                           // Console Command Prefix
    const CMD_PREFIX        = "bin/console ";                   // Console Command Prefix
    const CMD_SUFIX         = " > /dev/null &";                 // Console Command Suffix
    const WORKER            = "tasking:run";                    // Worker Start Console Command
    const SUPERVISOR        = "tasking:runsupervisor";          // Supervisor Start Console Command    
    
    /*
     *  Task Settings
     */    
    const DEFAULT_SETTINGS  = array(
        "label"                 =>      "Default Task Title",
        "description"           =>      "Default Task Description",
        "translation_domain"    =>      False,
    );
    
//==============================================================================
//      Definition           
//==============================================================================
        
    
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @abstract    Task Name (Unused in User HMI, Only for Admin)
     * @var         string
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private $name;
    
//==============================================================================
//      Task Display Informations
    
    /**
     * @abstract    Task Display Settings
     * @var         array
     * @ORM\Column(name="Settings", type="array")
     */
    private $settings = self::DEFAULT_SETTINGS;

//==============================================================================
//      Task Parameters           
    
    /**
     * @var string
     * @ORM\Column(name="ServiceName", type="string", length=250)
     */
    private $serviceName;

    /**
     * @var string
     * @ORM\Column(name="JobName", type="string", length=250)
     */
    private $jobName;

    /**
     * @var string
     * @ORM\Column(name="JobPriority", type="string", length=250)
     */
    private $jobPriority = self::DO_NORMAL;

    /**
     * @var array
     * @ORM\Column(name="JobParameters", type="array", nullable=TRUE)
     */
    private $jobParameters = array();

    /**
     * @var string
     * @ORM\Column(name="JobToken", type="string", length=250)
     */
    private $jobToken;

    /**
     * @abstract        Set if Job is A Static Job. Defined in configuration 
     * 
     * @var boolean
     * @ORM\Column(name="JobIsStatic", type="boolean", nullable=TRUE)
     */
    private $jobIsStatic = False;

    /**
     * @abstract        Repeat Delay in Minutes 
     * 
     * @var integer
     * @ORM\Column(name="JobFreq", type="integer", nullable=TRUE)
     */
    private $jobFrequency = False;
    
//==============================================================================
//      Status           
    
    /**
     * Count Number of Task Execution Tentatives
     * 
     * @var integer
     * @ORM\Column(name="NbTry", type="integer", nullable=TRUE)
     */
    private $try = 0;

    /**
     * Task is Pending
     * 
     * @var boolean
     * @ORM\Column(name="Running", type="boolean", nullable=TRUE)
     */
    private $running = False;

    /**
     * Task is Finished
     * 
     * @var boolean
     * @ORM\Column(name="Finished", type="boolean", nullable=TRUE)
     */
    private $finished = False;

    /**
     * @var \DateTime
     * @ORM\Column(name="StartedAt", type="datetime", nullable=TRUE)
     */
    private $startedAt;

    /**
     * @var integer
     * @ORM\Column(name="StartedAtTimeStamp", type="integer", nullable=TRUE)
     */
    private $startedAtTimeStamp;
    
    /**
     * @var integer
     * @ORM\Column(name="FinishedAt", type="datetime", nullable=TRUE)
     */
    private $finishedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="FinishedAtTimeStamp", type="integer", nullable=TRUE)
     */
    private $finishedAtTimeStamp;
    
    /**
     * @var string
     * @ORM\Column(name="StartedBy", type="string", length=250, nullable=TRUE)
     */
    private $startedBy;
    
    /**
     * @var \DateTime
     * @ORM\Column(name="PlannedAt", type="datetime", nullable=TRUE)
     */
    private $plannedAt;
    
//==============================================================================
//      Audit           
    
    /**
     * @var \DateTime
     * @ORM\Column(name="CreatedAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     * @ORM\Column(name="CreatedBy", type="string", length=250)
     */
    private $createdBy;

    /**
     * @var string
     * @ORM\Column(name="Fault", type="text", nullable=TRUE)
     */
    private $fault_str;
    
    
//==============================================================================
//      Object Operations
//==============================================================================

    /**
     * Get Current Server Identifier
     *
     * @return string
     */
    public function getCurrentServer()
    {
        $ServerName     =   filter_input(INPUT_SERVER, 'SERVER_ADDR');
        return empty($ServerName)?"Unknown":$ServerName;
    }

    /**
     *      @abstract    Init Task on Gearman Scheduler  
     */    
    public function Init() {

        //====================================================================//
        // Safety Check
        if ( $this->getFinished() && !$this->getJobIsStatic() ) {
//            echo "Your try to Start an Already Finished Task!! . PHP_EOL";
            return False;
        } 

        //====================================================================//
        // Init Task
        $this->setRunning     (True);
        $this->setStartedAt   (new \DateTime());
        $this->setStartedBy   ($this->getCurrentServer());
        $this->setTry         ($this->getTry() + 1 );
        
        return True;

    }   
    
    /**
     *      @abstract    End Task on Gearman Scheduler  
     */    
    public function Close() {

        //==============================================================================
        // End of Task Execution
        $this->setRunning(False);
        $this->setFinishedAt(new \DateTime());

    }            
    
//==============================================================================
//      LifeCycle Events
//==============================================================================
    
    
    /** @ORM\PrePersist() */    
    public function prePersist()
    {
        //====================================================================//
        // Set Created Date
        $this->setCreatedAt(new \DateTime);

        //====================================================================//
        // Set Created By
        $this->setCreatedBy(  $this->getCurrentServer() );
        
    }    
    
//==============================================================================
//      Process Operations
//==============================================================================
    
    /**
     *      @abstract    Start a Process on Local Machine (Server Node)
     */    
    public static function Process($Command,$Environement = Null) 
    {
        //====================================================================//
        // Finalize Command
        $RawCmd     =   Task::CMD_PREFIX . $Command;
        if ($Environement) {
            $RawCmd.=   " --env=" . $Environement;
        }
        //====================================================================//
        // Create Sub-Porcess
        $process = new Process($RawCmd . Task::CMD_SUFIX);
        
        //====================================================================//
        // Clean Working Dir
        $WorkingDirectory   =   $process->getWorkingDirectory();
        if (strrpos($WorkingDirectory, "/web") == (strlen($WorkingDirectory) - 4) ){
            $process->setWorkingDirectory(substr($WorkingDirectory, 0, strlen($WorkingDirectory) - 4));
        } 
        else if (strrpos($WorkingDirectory, "/app") == (strlen($WorkingDirectory) - 4) ){
            $process->setWorkingDirectory(substr($WorkingDirectory, 0, strlen($WorkingDirectory) - 4));
        }         
        //====================================================================//
        // Verify This Command Not Already Running
        $List = array();
        exec("pgrep '" . Task::CMD_PHP .  $RawCmd . "' -f",$List);
        if ( count($List) < 2 ) {
            //====================================================================//
            // Run Shell Command
            $process->start();     
        }        
        
        //====================================================================//
        // Wait for Script Startup
        usleep(1E4);             
        
        return True;
        
    }
    
//==============================================================================
//      Getters & Setters
//==============================================================================
    

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Task
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set user
     *
     * @param \stdClass $user
     *
     * @return Task
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \stdClass
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set jobName
     *
     * @param string $jobName
     *
     * @return Task
     */
    public function setJobName($jobName)
    {
        $this->jobName = $jobName;

        return $this;
    }

    /**
     * Get jobName
     *
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * Set jobParameters
     *
     * @param array $jobParameters
     *
     * @return Task
     */
    public function setJobParameters($jobParameters)
    {
        $this->jobParameters = $jobParameters;

        return $this;
    }

    /**
     * Get jobParameters
     *
     * @return array
     */
    public function getJobParameters()
    {
        return $this->jobParameters;
    }

    /**
     * Set running
     *
     * @param boolean $running
     *
     * @return Task
     */
    public function setRunning($running)
    {
        $this->running = $running;

        return $this;
    }

    /**
     * Get running
     *
     * @return boolean
     */
    public function getRunning()
    {
        return $this->running;
    }

    /**
     * Set startedAt
     *
     * @param \DateTime $startedAt
     *
     * @return Task
     */
    private function setStartedAt($startedAt)
    {
        //====================================================================//
        // Store date as DateTime
        $this->startedAt            = $startedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->startedAtTimeStamp  = $startedAt->getTimestamp();
        return $this;
    }

    /**
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set finishedAt
     *
     * @param \DateTime $finishedAt
     *
     * @return Task
     */
    private function setFinishedAt($finishedAt)
    {
        //====================================================================//
        // Store date as DateTime
        $this->finishedAt           = $finishedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->finishedAtTimeStamp  = $finishedAt->getTimestamp();
        
        return $this;
    }

    /**
     * Get finishedAt
     *
     * @return \DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * Set startedBy
     *
     * @param string $startedBy
     *
     * @return Task
     */
    private function setStartedBy($startedBy)
    {
        $this->startedBy = $startedBy;

        return $this;
    }

    /**
     * Get startedBy
     *
     * @return string
     */
    public function getStartedBy()
    {
        return $this->startedBy;
    }

    /**
     * Set try
     *
     * @param integer $try
     *
     * @return Task
     */
    private function setTry($try)
    {
        $this->try = $try;

        return $this;
    }

    /**
     * Get try
     *
     * @return integer
     */
    public function getTry()
    {
        return $this->try;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Task
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return Task
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set finished
     *
     * @param boolean $finished
     *
     * @return Task
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Get finished
     *
     * @return boolean
     */
    public function getFinished()
    {
        return $this->finished;
    }
    
    
    /**
     * Set serviceName
     *
     * @param string $serviceName
     *
     * @return Task
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Get serviceName
     *
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }


    /**
     * Set jobPriority
     *
     * @param string $jobPriority
     *
     * @return Task
     */
    public function setJobPriority($jobPriority)
    {
        $this->jobPriority = $jobPriority;

        return $this;
    }

    /**
     * Get jobPriority
     *
     * @return string
     */
    public function getJobPriority()
    {
        return $this->jobPriority;
    }

    /**
     * Set faultStr
     *
     * @param string $faultStr
     *
     * @return Task
     */
    public function setFaultStr($faultStr)
    {
        $this->fault_str = $faultStr;

        return $this;
    }

    /**
     * Get faultStr
     *
     * @return string
     */
    public function getFaultStr()
    {
        return $this->fault_str;
    }

    /**
     * Set jobToken
     *
     * @param string $jobToken
     *
     * @return Task
     */
    public function setJobToken($jobToken)
    {
        $this->jobToken = $jobToken;

        return $this;
    }

    /**
     * Get jobToken
     *
     * @return string
     */
    public function getJobToken()
    {
        return $this->jobToken;
    }

    /**
     * Set jobIsStatic
     *
     * @param boolean $jobIsStatic
     *
     * @return Task
     */
    public function setJobIsStatic($jobIsStatic)
    {
        $this->jobIsStatic = $jobIsStatic;

        return $this;
    }

    /**
     * Get jobIsStatic
     *
     * @return boolean
     */
    public function getJobIsStatic()
    {
        return $this->jobIsStatic;
    }

    /**
     * Set jobFrequency
     *
     * @param integer $jobFrequency
     *
     * @return Task
     */
    public function setJobFrequency($jobFrequency)
    {
        $this->jobFrequency = $jobFrequency;

        return $this;
    }

    /**
     * Get jobFrequency
     *
     * @return integer
     */
    public function getJobFrequency()
    {
        return $this->jobFrequency;
    }

    /**
     * Set plannedAt
     *
     * @param \DateTime $plannedAt
     *
     * @return Task
     */
    public function setPlannedAt($plannedAt)
    {
        $this->plannedAt = $plannedAt;

        return $this;
    }

    /**
     * Get plannedAt
     *
     * @return \DateTime
     */
    public function getPlannedAt()
    {
        return $this->plannedAt;
    }
    
    
    /**
     * Set setting
     *
     * @param string    $Domain
     * @param mixed     $Value
     *
     * @return User
     */
    public function setSetting($Domain,$Value)
    {
        //==============================================================================
        // Update Domain Setting
        $this->settings[$Domain] = $Value;
        return $this;
    }
    
    /**
     * Set settings
     *
     * @param array $settings
     *
     * @return User
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }
    
}
