<?php

namespace Splash\Tasking\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @abstract    Base Class for Background Jobs Definition
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
//abstract 
class AbstractBatchJob extends AbstractJob {
    
    //==============================================================================
    //  Constants Definition           
    //==============================================================================

    /*
     * @abstract    Job Action Method Name
     * @var string
     */    
    const action            = "batch";
    
    /*
     * @abstract    Batch Job Inputs List Method Name
     * @var string
     */    
    const batchList         = "configure";
    
    /*
     * @abstract    Batch Job Action Method Name
     * @var string
     */    
    const batchAction       = "execute";
    
    /*
     * @abstract    Parameter - Stop on Errors
     *                  => If Set, if one of the batch action return False, batch action is stopped
     * 
     * @var bool
     */    
    const stopOnError       = TRUE;
    
    /*
     * @abstract    Parameter - Batch Action Pagination. 
     *                  => Number of tasks to start on each batch step
     * 
     * @var bool
     */    
    const paginate          = 1;    
    
    //====================================================================//
    // Define Initial State for a Batch Action
    static $State           = array(
            //==============================================================================
            //  General Status Flags
            'isCompleted'               => False,
            'isListLoaded'              => False,

            //==============================================================================
            //  Batch Counters
            'tasksCount'                => 0,
            'jobsCount'                 => 0,
            'jobsCompleted'             => 0,
            'jobsSuccess'               => 0,
            'jobsError'                 => 0,

            //==============================================================================
            //  Batch Execution
            "currentJob"                => 0,
    );    
    
    public function __construct()
    {
        parent::__construct();
        
        $this->setInputs(array());
        $this->setState(array());
        $this->setToken(get_class($this) . "::" . static::action);
    }  
    
////==============================================================================
////  Variables Definition           
////==============================================================================
//
//    /**
//     * @abstract    Batch Job User Inputs => Exacted 
//     *      
//     * @var int
//     */
//    protected $frequency    = 3600;    
    
//==============================================================================
//      Prototypes for User Batch Job
//==============================================================================

    /*
     * @abstract    Overide this function to generate list of your batch tasks inputs
     */    
    public function configure() : array {
        
        $BatchList = array();
        for ( $i=1 ; $i < 3 ; $i++) {
            $BatchList[] = array( 
                "name"      => "Job " . $i,
                "delay"     => $i ,
                ); 
        }
        
        return $BatchList;
    }
    
    /*
     * @abstract    Overide this function to perform your task
     */    
    public function execute(array $Inputs = []) : bool {

        echo "<h4>Default Batch Action : " . $Inputs["name"] . "</h4>";
        echo " => Delay of : " . $Inputs["delay"] . " Seconds</br>";
        echo "Overide Execute function to define your own Batch Action </br>";
        sleep($Inputs["delay"]);
        
        return True;
    }
    
//==============================================================================
//      Batch Job Execution Management
//==============================================================================

    /*
     * @abstract    Main function for Batch Jobs Management
     */    
    public function batch() {
        
        //==============================================================================
        //      Check Batch Job List is Loaded (Or Try to Load It)
        if ( !$this->getStateItem("isListLoaded") && !$this->batchLoadJobsList() ) {
            return False;
        } 
        
        //==============================================================================
        //      Safety Ckeck - Ensure Execute Method Exists
        if ( !method_exists($this, static::batchAction ) ) {
            $this->setStateItem("isCompleted", True);
            return True;
        } 

        //====================================================================//
        // Load Current Batch State
        $State                  = $this->getState();
        $State["tasksCount"]++;
        
        //==============================================================================
        //      Execute Batch Tasks
        //==============================================================================
        
        //====================================================================//
        // Init Task Planification Counters
        $TaskStart      = $State["currentJob"];  
        $TaskMax        = $State["jobsCount"] - 1;  
        $TaskEnd        = static::paginate    ?   ($TaskStart + static::paginate) : $TaskMax; 
        if ( $TaskEnd > $TaskMax ) {
            $TaskEnd = $TaskMax;
        }       
        
        //====================================================================//
        // Batch Execution Loop
        for ( $Index = $TaskStart ; $Index <= $TaskEnd ; $Index++ ) {
            
            //==============================================================================
            //      Update State
            $State["currentJob"]++;
            
            //==============================================================================
            //      Safety Ckeck - Ensure Input Array Exists
            if ( is_null($JobInputs = $this->getJobInputs($Index)) ) {
                $this->setStateItem("isCompleted", True);
                return False;
            } 

            //==============================================================================
            //      Execute User Batch Job
            $JobsResult = $this->{ static::batchAction }($JobInputs);            
            
            //==============================================================================
            //      Update State
            $State["jobsCompleted"]++;
            $JobsResult ? $State["jobsSuccess"]++ : $State["jobsError"]++;
            $this->setState($State);
            
            //==============================================================================
            //      Manage Stop on Error
            if ( !$JobsResult && static::stopOnError ) {
                $this->setStateItem("isCompleted", True);
                return False;
            } 
            
            
        }
        
        //==============================================================================
        //      Manage Stop on Error
        if ( $State["jobsCompleted"] >= $State["jobsCount"] ) {
            $this->setStateItem("isCompleted", True);
        }         
        
        return True;
    }
    
    /*
     * @abstract   Load Jobs Batch Actions Inputs fro User function 
     */    
    public function batchLoadJobsList() : bool {
        
        //==============================================================================
        //      Safety Ckeck - Ensure Configure Method Exists
        if ( !method_exists($this, static::batchList ) ) {
            return False;
        } 

        //==============================================================================
        //      Read List of Jobs from User Function
        $JobsInputs = $this->{ static::batchList }($this->getInputs());
        
        //==============================================================================
        //      Check List is not Empty
        if ( empty($JobsInputs) ) {
            $this->setStateItem("isCompleted", True);
            return True;
        }
        
        //==============================================================================
        //      Setup List 
        $this->setJobsList($JobsInputs);
        
        //==============================================================================
        //      Init Batch State
        $State                  =   static::$State;
        $State["isListLoaded"]  =   True;
        $State["jobsCount"]     =   count($JobsInputs);
        $this->setState($State);

        return True;
    }
    
    /*
     * @abstract    Check if batch actions are completed or task needs to be executed again (pagination)
     */    
    public function isCompleted() : bool {
        return $this->inputs["state"]["isCompleted"];
    }
    
    /*
     * @abstract    Check if Errors have occured during batch action
     */    
    public function hasErrors() : bool {
        return $this->inputs["state"]["jobsError"] ? True : False;
    }    
    
//==============================================================================
//      Specific Getters & Setters
//==============================================================================
    
    /**
     * Set Job User Inputs
     *
     * @param array $inputs
     *
     * @return Task
     */
    public function setInputs($inputs)
    {
        $this->inputs["inputs"] = $inputs;

        return $this;
    }
    
    /**
     * Get Job User Inputs
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs["inputs"];
    }
        
    /**
     * Set Job Status
     *
     * @param array $state
     *
     * @return Task
     */
    public function setState($state)
    {
        //==============================================================================
        //  Init State Array using OptionResolver
        $resolver = (new OptionsResolver())->setDefaults(static::$State);
        //==============================================================================
        //  Update State Array using OptionResolver        
        try {
            $this->inputs["state"] = $resolver->resolve($state);
        //==============================================================================
        //  Invalid Field Definition Array   
        } catch (UndefinedOptionsException $ex) {
            $this->inputs["state"] = static::$State;
        } catch (InvalidOptionsException $ex) {
            $this->inputs["state"] = static::$State;
        } 
        
        return $this;
    }
    
    /**
     * Get Job Status
     *
     * @return array
     */
    public function getState()
    {
        return $this->inputs["state"];
    }
        
    /**
     * Set Batch Action State Item
     *
     * @param string    $Index
     * @param mixed     $Value
     *
     * @return self
     */
    public function setStateItem($Index,$Value)
    {
        //==============================================================================
        // Read Full State Array
        $State = $this->getState();
        //==============================================================================
        // Update Item
        $State[$Index] = $Value;
        //==============================================================================
        // Update Full State Array
        $this->setState($State);
        
        return $this;
    }
    
    /**
     * Get Batch Action State Item
     *
     * @param string    $Index
     *
     * @return mixed
     */
    public function getStateItem($Index)
    {
        if ( isset($this->inputs["state"][$Index]) ) {
            return $this->inputs["state"][$Index];
        }
        return Null;
    }
    
    /**
     * Set Jobs List
     *
     * @param array $list
     *
     * @return Task
     */
    public function setJobsList(array $list)
    {
        //==============================================================================
        // Parse Jobs Inputs List to a Numeric Array
        $this->inputs["jobs"] = array();
        foreach ($list as $job) {
            $this->inputs["jobs"][] = $job;
        }
        return $this;
    }
    
    /**
     * Get Jobs List
     *
     * @return array
     */
    public function getJobList()
    {
        return $this->inputs["jobs"];
    }

    /**
     * Get Job Inputs
     *
     * @return array
     */
    public function getJobInputs($Index)
    {
        if ( isset($this->inputs["jobs"][$Index]) ) {
            return $this->inputs["jobs"][$Index];
        }
        return Null;
    }    

}
