<?php

namespace Splash\Tasking\Tests\Services;

/**
 * @abstract    Tasks Sampling Service
 *              Collection of Dummy Specific Testing Functions
 */
class TasksSamplingService 
{
    /*
     *  Fault String
     */
    public $fault_str;     
    
//====================================================================//
// *******************************************************************//
//  Very Simple Functions
// *******************************************************************//
//====================================================================//

    /**
     * @abstract    Test Task Inputs
     *
     * @param Array    $Inputs    Array with job parameters
     *
     * @return boolean
     */
    public function InputsTestTask($Inputs)
    {
        //====================================================================//
        // Safety Checks
        if (!is_array($Inputs) && !is_a($Inputs, "ArrayObject") ){
            $this->fault_str    =   "Inputs are not an Array";
            return False;
        }
        return True;
    }   
    
    
    /**
     * @abstract    Test Task Delay
     *
     * @param Array    $Inputs    Array with job parameters
     *
     * @return boolean
     */
    public function DelayTask($Inputs)
    {
        //====================================================================//
        // Pause
        if (array_key_exists("Delay", $Inputs)  ) {
            sleep($Inputs["Delay"]);
        }           
        return True;
    }   
    
    /**
     * @abstract    Test Task Delay
     *
     * @param Array    $Inputs    Array with job parameters
     *
     * @return boolean
     */
    public function MicroDelayTask($Inputs)
    {
        //====================================================================//
        // Pause
        if (array_key_exists("Delay", $Inputs)  ) {
            usleep($Inputs["Delay"]);
        }           
        return True;
    }   
    
//====================================================================//
// *******************************************************************//
//  Faulty Functions
// *******************************************************************//
//====================================================================//

    /**
     * @abstract    Test Task with Syntax Error
     *
     * @param Array    $Inputs    Array with job parameters
     *
     * @return boolean
     */
    public function ExceptionTask($Inputs)
    {
        //====================================================================//
        // Generate an Exception!
        throw new \Exception('This Is A TEST Exception');
    }     
    
    /**
     * @abstract    Test Task with Syntax Error
     *
     * @param Array    $Inputs    Array with job parameters
     *
     * @return boolean
     */
    public function InErrorTask($Inputs)
    {
        //====================================================================//
        // Generate an Error!
        $Nothing    =   new UnexistingClass();
             
        return True;
    }   
    
    
    
    
}