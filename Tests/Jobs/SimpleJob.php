<?php

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\AbstractJob;

/**
 * @abstract    Demonstartion fo Simple Background Jobs
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class SimpleJob extends AbstractJob {
    
//==============================================================================
//  Constants Definition           
//==============================================================================

    
    /*
     * @abstract    Job Priority
     * @var int
     */    
    const priority          = Task::DO_LOW;
    
    /**
     * @abstract    Job Inputs => Load here all inputs parameters for your task
     *      
     * @var array
     */
    protected $inputs      = ["delay" => 5];    
    
    
    /**
     * @abstract    Job Token is Used for concurency Management
     *              You can set it directly by overriding this constant 
     *              or by writing an array of parameters to setJobToken()
     * @var string
     */
    protected $token       = "JOB_SIMPLE";    
    

    /*
     * @abstract Job Display Settings
     */    
    protected $settings   = array(
        "label"                 =>      "Simple Job Demo",
        "description"           =>      "Demonstration of a Simple Job",
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
    public function validate(array $Inputs = []) {
        echo "Simple Job => Validate Inputs </br>";
        if (is_integer($Inputs["delay"])) {
            echo "Simple Job => Delay is a Integer </br>";
        } 
        return True;
    }
    
    /*
     * @abstract    Overide this function to prepare your class for it's execution
     */    
    public function prepare() {
        echo "Simple Job => Prepare for Action </br>";
        return True;
    }
    
    /*
     * @abstract    Overide this function to perform your task
     */    
    public function execute(array $Inputs = []) {
        echo "Simple Job => Execute a " . $Inputs["delay"]. " Second Pause </br>";
        sleep($Inputs["delay"]);
        return True;
    }
    
    /*
     * @abstract    Overide this function to validate results of your task or perform post-actions
     */    
    public function finalize() {
        echo "Simple Job => Finalize Action </br>";
        return True;
    }
    
    /*
     * @abstract Overide this function to close your task
     */    
    public function close() {
        echo "Simple Job => Close Action </br>";
        return True;
    }
    
}
