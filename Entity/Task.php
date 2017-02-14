<?php

namespace Splash\Tasking\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Console\Output\OutputInterface;

// OptionResolver for Task Settings Management
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * @abstract    Splash Task Storage Object
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
    const DO_HIGHEST        = 10;
    const DO_HIGH           = 7;
    const DO_NORMAL         = 5;
    const DO_LOW            = 3;
    const DO_LOWEST         = 1;
    
    /*
     *  Task Settings
     */    
    static $DEFAULT_SETTINGS  = array(
        "label"                 =>      "Default Task Title",
        "description"           =>      "Default Task Description",
        "translation_domain"    =>      False,
        "translation_params"    =>      array()
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
    //==============================================================================
    
    /**
     * @abstract    Task Display Settings
     * @var         array
     * @ORM\Column(name="Settings", type="array")
     */
    private $settings = array();

    //==============================================================================
    //      Task User Parameters           
    //==============================================================================
    
    /**
     * @var string
     * @ORM\Column(name="JobClass", type="string", length=250)
     */
    private $jobClass;

    /**
     * @var string
     * @ORM\Column(name="JobAction", type="string", length=250)
     */
    private $jobAction;

    /**
     * @var string
     * @ORM\Column(name="JobPriority", type="string", length=250)
     */
    private $jobPriority = self::DO_NORMAL;

    /**
     * @var array
     * @ORM\Column(name="JobInputs", type="array", nullable=TRUE)
     */
    private $jobInputs = array();

    /**
     * @var string
     * @ORM\Column(name="JobToken", type="string", length=250, nullable=TRUE)
     */
    private $jobToken;
    
    /**
     * @var string
     * @ORM\Column(name="JobIndexKey1", type="string", length=250, nullable=TRUE)
     */
    private $jobIndexKey1;    

    /**
     * @var string
     * @ORM\Column(name="JobIndexKey2", type="string", length=250, nullable=TRUE)
     */
    private $jobIndexKey2;    

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
    private $jobFrequency = Null;
    
    //==============================================================================
    //      Status           
    //==============================================================================
    
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
    
    /**
     * @var \DateTime
     * @ORM\Column(name="PlannedAtTimeStamp", type="integer", nullable=TRUE)
     */
    private $plannedAtTimeStamp;
    
    //==============================================================================
    //      Audit           
    //==============================================================================
    
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
    
    /**
     * @var string
     * @ORM\Column(name="Outputs", type="text", nullable=TRUE)
     */
    private $outputs;
    
    function __construct() {
        $this->setSettings(static::$DEFAULT_SETTINGS);
    }


//==============================================================================
//      Task Execution Management
//==============================================================================

    /**
     * @abstract    Main Function for Job Execution  
     */    
    public function Execute(OutputInterface $Output) {   
        
        //==============================================================================
        // Init Task Execution
        $Result = False;        // Task is Considered as Fail until returned Passed by JobInterface
        ob_start();             // Turn On Output Buffering to Get Task Outputs Captured
                    
            
        //==============================================================================
        // Execute Requested Operation
        //==============================================================================
        try {
            //==============================================================================
            // Execute Job Self Validate & Prepare Methods
            if ( $this->job->validate() && $this->job->prepare() ) {
                
                //==============================================================================
                // Execute Job Action
                $Result = $this->job->{$this->getJobAction()}();
                if ( !$Result ) {
                    $this->setFaultStr("An error occured when executing this Job.", $Output);
                }
            
                //==============================================================================
                // Execute Job Self Finalize & Close Methods
                if ( !$this->job->finalize() || !$this->job->close() ) {
                    $this->setFaultStr("An error occured when closing this Job.", $Output);
                }
            } else {
                $this->setFaultStr("Unable to initiate this Job.", $Output);
            }
        }            
        //==============================================================================
        // Catch Any Exceptions that may occur during task execution
        catch( \Exception $e) {
            $this->setFaultStr($e->getMessage() . PHP_EOL . $e->getFile() . " Line " . $e->getLine(), $Output);
        }
        
        //==============================================================================
        // Flush Output Buffer
        $this->appendOutputs(ob_get_contents());
        ob_end_clean();
        
        //==============================================================================
        // If Job is Successful => Store Status
        if ( $Result ) {
            $this->setFinished(True);
        }
        return $Result;
        
    } 
    
    
    /**
     * @abstract    Validate Job For Execution  
     */    
    public function Validate(OutputInterface $Output, $Container) {

        //==============================================================================
        // Load Requested Class
        if ( empty($JobClass = $this->getJobClass()) || !class_exists($this->getJobClass()) ) {
            $this->setFaultStr("Unable to find Requested Job Class : " . $JobClass, $Output);
            return False;
        }        
        $this->job = new $JobClass();
        
        //====================================================================//
        // Job Class is SubClass of Base Job Class
        if ( !is_a($this->job, "Splash\Tasking\Model\AbstractJob")) {
            return False;
        } 
        
        //====================================================================//
        // Job Class is Container Aware
        if ($this->job instanceof ContainerAwareInterface) {
            $this->job->setContainer($Container);
        }

        //==============================================================================
        // Verify Requested Method Exists
        if ( empty($this->getJobAction()) || !method_exists($this->job, $this->getJobAction()) ) {
            $this->setFaultStr("Unable to find Requested Function", $Output);
            return False;
        }
        
        return True;
    }

    /**
     * @abstract    Prepare Job For Execution  
     */    
    public function Prepare(OutputInterface $Output) {

        //====================================================================//
        // Init Task
        $this->setRunning       (True);
        $this->setFinished      (False);
        $this->setStartedAt     (new \DateTime());
        $this->setStartedBy     ($this->getCurrentServer());
        $this->setTry           ($this->getTry() + 1 );
        $this->setFaultStr      (Null);

        //==============================================================================
        // Check Task Parameters
        if ( !is_array($this->getJobInputs()) && !is_a($this->getJobInputs(), "ArrayObject") ) {
            $this->setFaultStr( "Wrong Inputs Format" , $Output);
            return False;
        }
        
        //====================================================================//
        // Safety Check
        if ( $this->getFinished() && !$this->getJobIsStatic() ) {
            $this->setFaultStr( "Your try to Start an Already Finished Task!!" , $Output);
            return False;
        } 
       
        //====================================================================//
        // Init User Job
        $this->job->__set("inputs" , $this->getJobInputs());
        
        //====================================================================//
        // User Information             
        if ($Output->isVerbose()) {
            $Output->writeln('<info> Execute : ' . $this->getJobClass() . " -> " . $this->getJobAction() . '  (' . $this->getId()  .  ')</info>');
            $Output->writeln('<info> Parameters : ' . print_r($this->getJobInputs(),True) . '</info>');
        } else {
            $Output->write('<info>o</info>');
        }    
        
//        set_error_handler(function($errno, $errstr, $errfile, $errline ){
//            throw new \Exception($errstr, $errno, 0, $errfile, $errline);
//        });

        return True;

    } 
    
    /**
     * @abstract    End Task on Scheduler  
     * 
     * @param   int $MaxTry Max number of retry. Once reached, task is forced to finished.
     */    
    public function Close($MaxTry) {

        
        //==============================================================================
        // End of Task Execution
        $this->setRunning(False);
        $this->setFinishedAt(new \DateTime());
        

        //==============================================================================
        // If Static Task => Set Next Planned Execution Date
        if ( $this->getJobIsStatic()) {
            $this->setTry(0);
            $this->setPlannedAt( 
                    new \DateTime( "+" . $this->getJobFrequency() . "Minutes ")
                    );
        }
        
        //==============================================================================
        // Store Task Result
        if ( $this->getTry() > $MaxTry ) {
            $this->setFinished(True);
            return;
        }        
              
        if ( is_a($this->job, "Splash\Tasking\Model\AbstractBatchJob") ) {
            //==============================================================================
            // If Batch Task Not Completed => Setup For Next Execution
            if ( !$this->job->getStateItem("isCompleted") ) {
                $this->setTry(0);
                $this->setFinished(False);
            }
            //==============================================================================
            // Backup Inputs Parameters For Next Actions
            $this->setJobInputs($this->job->__get("inputs"));
        } 
    }            
    
    /**
     * Set faultStr
     *
     * @param string $faultStr
     *
     * @return Task
     */
    public function setFaultStr($faultStr, OutputInterface $Output = Null)
    {
        $this->fault_str = $faultStr;
        
        if ( $Output ) {
            $Output->writeln('<error>' . $faultStr . '</error>');
        }

        return $this;
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
//      Specific Getters & Setters
//==============================================================================
    
    /**
     * Get Current Server Identifier
     *
     * @return string
     */
    public function getCurrentServer()
    {
        //====================================================================//
        // Load Current Server Infos
        $System    = posix_uname();
        //====================================================================//
        // Return machine Name
        return $System["nodename"];
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
     * Set plannedAt
     *
     * @param \DateTime $plannedAt
     *
     * @return Task
     */
    private function setPlannedAt($plannedAt)
    {
        //====================================================================//
        // Store date as DateTime
        $this->plannedAt           = $plannedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->plannedAtTimeStamp  = $plannedAt->getTimestamp();
        
        return $this;
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
        // Read Full Settings Array
        $Settings = $this->getSettings();
        //==============================================================================
        // Update Domain Setting
        $Settings[$Domain] = $Value;
        //==============================================================================
        // Update Full Settings Array
        $this->setSettings($Settings);
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
        
        //==============================================================================
        //  Init Settings Array using OptionResolver
        $resolver = (new OptionsResolver())->setDefaults(static::$DEFAULT_SETTINGS);
        //==============================================================================
        //  Update Settings Array using OptionResolver        
        try {
            $this->settings = $resolver->resolve($settings);
        //==============================================================================
        //  Invalid Field Definition Array   
        } catch (UndefinedOptionsException $ex) {
            $this->settings  =   static::$SETTINGS;
        } catch (InvalidOptionsException $ex) {
            $this->settings  =   static::$SETTINGS;
        } 
            
        return $this;        
    }
    
    /**
     * Append Task Outputs
     *
     * @return string
     */
    public function appendOutputs($Text)
    {
        return $this->outputs .= $Text . PHP_EOL;
    }    

    /**
     * Get jobInputs as a string
     *
     * @return string
     */
    public function getJobInputsStr() : string
    {
        return "<PRE>" . print_r($this->jobInputs , True) . "</PRE>";
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
     * Get settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set jobClass
     *
     * @param string $jobClass
     *
     * @return Task
     */
    public function setJobClass($jobClass)
    {
        $this->jobClass = $jobClass;

        return $this;
    }

    /**
     * Get jobClass
     *
     * @return string
     */
    public function getJobClass()
    {
        return $this->jobClass;
    }

    /**
     * Set jobAction
     *
     * @param string $jobAction
     *
     * @return Task
     */
    public function setJobAction($jobAction)
    {
        $this->jobAction = $jobAction;

        return $this;
    }

    /**
     * Get jobAction
     *
     * @return string
     */
    public function getJobAction()
    {
        return $this->jobAction;
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
     * Set jobInputs
     *
     * @param array $jobInputs
     *
     * @return Task
     */
    public function setJobInputs($jobInputs)
    {
        $this->jobInputs = $jobInputs;

        return $this;
    }

    /**
     * Get jobInputs
     *
     * @return array
     */
    public function getJobInputs()
    {
        return $this->jobInputs;
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

        if ( $this->getFinished() ) {
            $this->setPlannedAt( 
                new \DateTime( "+" . $this->getJobFrequency() . "Minutes ")
                );
        } else {
            $this->setPlannedAt(new \DateTime());
        }
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
     * Set jobIndexKey1
     *
     * @param string $jobIndexKey1
     *
     * @return Task
     */
    public function setJobIndexKey1($jobIndexKey1)
    {
        $this->jobIndexKey1 = $jobIndexKey1;

        return $this;
    }

    /**
     * Get jobIndexKey1
     *
     * @return string
     */
    public function getJobIndexKey1()
    {
        return $this->jobIndexKey1;
    }

    /**
     * Set jobIndexKey2
     *
     * @param string $jobIndexKey2
     *
     * @return Task
     */
    public function setJobIndexKey2($jobIndexKey2)
    {
        $this->jobIndexKey2 = $jobIndexKey2;

        return $this;
    }

    /**
     * Get jobIndexKey2
     *
     * @return string
     */
    public function getJobIndexKey2()
    {
        return $this->jobIndexKey2;
    }
    
    /**
     * Set try
     *
     * @param integer $try
     *
     * @return Task
     */
    public function setTry($try)
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
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set startedAtTimeStamp
     *
     * @param integer $startedAtTimeStamp
     *
     * @return Task
     */
    public function setStartedAtTimeStamp($startedAtTimeStamp)
    {
        $this->startedAtTimeStamp = $startedAtTimeStamp;

        return $this;
    }

    /**
     * Get startedAtTimeStamp
     *
     * @return integer
     */
    public function getStartedAtTimeStamp()
    {
        return $this->startedAtTimeStamp;
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
     * Set finishedAtTimeStamp
     *
     * @param integer $finishedAtTimeStamp
     *
     * @return Task
     */
    public function setFinishedAtTimeStamp($finishedAtTimeStamp)
    {
        $this->finishedAtTimeStamp = $finishedAtTimeStamp;

        return $this;
    }

    /**
     * Get finishedAtTimeStamp
     *
     * @return integer
     */
    public function getFinishedAtTimeStamp()
    {
        return $this->finishedAtTimeStamp;
    }

    /**
     * Set startedBy
     *
     * @param string $startedBy
     *
     * @return Task
     */
    public function setStartedBy($startedBy)
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
     * Get plannedAt
     *
     * @return \DateTime
     */
    public function getPlannedAt()
    {
        return $this->plannedAt;
    }

    /**
     * Get plannedAtTimeStamp
     *
     * @return integer
     */
    public function getPlannedAtTimeStamp()
    {
        return $this->plannedAtTimeStamp;
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
     * Get faultStr
     *
     * @return string
     */
    public function getFaultStr()
    {
        return $this->fault_str;
    }
    
    /**
     * Get Task Outputs
     *
     * @return string
     */
    public function getOutputs()
    {
        return $this->outputs;
    }
    
}
