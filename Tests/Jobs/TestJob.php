<?php

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Model\AbstractJob;

/**
 * @abstract    Tests Of Simple Background Jobs
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class TestJob extends AbstractJob {
    
//==============================================================================
//  Constants Definition           
//==============================================================================
    
    /**
     * @abstract    Job Inputs => Load here all inputs parameters for your task
     *      
     * @var array
     */
    protected $inputs      = array(
            // Execute Customs Delays
            "Delay-Ms"                  => 0,
            "Delay-S"                   => 1,
        
            // Simulate Configuration Errors
            "Error-Wrong-Action"        => False,
            "Error-Wrong-Priority"      => False,
            "Error-On-Validate"         => False,
            "Error-On-Prepare"          => False,
            "Error-On-Execute"          => False,
            "Error-On-Finalize"         => False,
            "Error-On-Close"            => False,
        
            // Simulate Exceptions
            "Exception-On-Validate"     => False,
            "Exception-On-Prepare"      => False,
            "Exception-On-Execute"      => False,
            "Exception-On-Finalize"     => False,
            "Exception-On-Close"        => False,
        );    
    
    
    /**
     * @abstract    Job Token is Used for concurrency Management
     *              You can set it directly by overriding this constant 
     *              or by writing an array of parameters to setJobToken()
     * @var string
     */
    protected $token       = "TEST_JOB";    
    

    /*
     * @abstract Job Display Settings
     */    
    protected $settings   = array(
        "label"                 =>      "Simple Job for Test",
        "description"           =>      "Custom Simple Job for Bundle Testing",
        "translation_domain"    =>      False,
        "translation_params"    =>      array()
    );
    
//==============================================================================
//      Job Setup 
//==============================================================================
    
    public function setDelay(int $Delay) {
        $this->setInputs(["delay" => $Delay]);
    }


//==============================================================================
//      Task Execution Management
//==============================================================================

    /*
     * @abstract    Overide this function to validate you Input parameters
     */    
    public function validate() : bool 
    {
        echo "Simple Job => Validate Inputs </br>";
        
        //====================================================================//
        // Load Inputs Parameters
        $Inputs = $this->getInputs();
                
        //====================================================================//
        // Validate Delay Sec
        if ( isset($Inputs["Delay-S"]) && !is_integer($Inputs["Delay-S"])) {
            echo " => Delay Sec is not a Integer value!</br>";
            return False;
        } 
        //====================================================================//
        // Validate Delay Ms
        if ( isset($Inputs["Delay-Ms"]) && !is_integer($Inputs["Delay-Ms"])) {
            echo " => Delay Ms is not a Integer value!</br>";
            return False;
        } 
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Validate");
        
        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Validate");
    }
    
    /*
     * @abstract    Overide this function to prepare your class for it's execution
     */    
    public function prepare() : bool {
        echo "Simple Job => Prepare for Action </br>";
        
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Prepare");
        
        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Prepare");
    }
    
    /*
     * @abstract    Overide this function to perform your task
     */    
    public function execute(array $Inputs = []) : bool {
        echo "Simple Job => Execute Requted Actions! </br>";
        
        //====================================================================//
        // Execute Requested Pauses
        $this->doDelays();
        
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Execute");
        
        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Execute");
    }
    
    /*
     * @abstract    Overide this function to validate results of your task or perform post-actions
     */    
    public function finalize() : bool {
        echo "Simple Job => Finalize Action </br>";
        
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Finalize");
        
        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Finalize");
    }
    
    /*
     * @abstract Overide this function to close your task
     */    
    public function close() : bool {
        echo "Simple Job => Close Action </br>";
        
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Close");
        
        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Close");
    }
    
    /**
     * Get Job Action Name
     *
     * @return string
     */
    public function getAction()
    {
        //====================================================================//
        // Simulate Wrong Action Name
        if ( is_array($this->inputs) && isset($this->inputs["Error-Wrong-Action"]) && $this->inputs["Error-Wrong-Action"]) {
            return "WrongAction";
        } 
        
        return static::action;
    }    
    
    /**
     * Get Job Priority
     *
     * @return int
     */
    public function getPriority()
    {
        //====================================================================//
        // Simulate Wrong Priority Format
        if ( is_array($this->inputs) && isset($this->inputs["Error-Wrong-Priority"]) && $this->inputs["Error-Wrong-Priority"]) {
            return "TextFormat";
        } 
        
        return static::priority;
    }
    
    /*
     * @abstract Execute requested Delays
     */    
    public function doDelays() {
        //====================================================================//
        // Milliseconds Delay
        if ( isset($this->inputs["Delay-Ms"]) && $this->inputs["Delay-Ms"]) {
            echo "Simple Job => Wait for " . $this->inputs["Delay-Ms"] . " Ms </br>";
            usleep( 1E3 * $this->inputs["Delay-Ms"] );
        } 
        //====================================================================//
        // Seconds Delay
        if ( isset($this->inputs["Delay-S"]) && $this->inputs["Delay-S"]) {
            echo "Simple Job => Wait for " . $this->inputs["Delay-S"] . " Seconds </br>";
            sleep( $this->inputs["Delay-S"] );
        } 
    }
    
    /*
     * @abstract Return False (Error) if Requested by User
     */    
    public function doErrorReturn($MethodName) {
        //====================================================================//
        // Compute Input Parameter Index
        $Id = "Error-On-" . $MethodName;
        //====================================================================//
        // Trow exception if requested!
        if ( isset($this->inputs[$Id]) && $this->inputs[$Id]) {
            echo "You requeted Job Error on " . $MethodName . " Method.";
            return False;
        } 
        return True;
    }  
    
    /*
     * @abstract Thow an Exception if Requested by User
     */    
    public function doThrowException($MethodName) {
        //====================================================================//
        // Compute Input Parameter Index
        $Id = "Exception-On-" . $MethodName;
        //====================================================================//
        // Trow exception if requested!
        if ( isset($this->inputs[$Id]) && $this->inputs[$Id]) {
            throw new \Exception("You requeted Job to Fail on " . $MethodName . " Method.");
        } 
    }    
    
}
