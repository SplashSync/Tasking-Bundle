<?php

namespace Splash\Tasking\Tests\Services;

/**
 * @abstract    Tasks Sampling Service
 *              Collection of Dummy Specific Testing Functions
 */
class TasksSamplingService 
{
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
            echo "Service Job => Wait for " . $Inputs["Delay"] . " Seconds </br>";
            sleep($Inputs["Delay"]);
        }           
        return True;
    }   
}