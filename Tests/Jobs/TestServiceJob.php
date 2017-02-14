<?php

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Model\AbstractServiceJob;

/**
 * @abstract    Demonstartion fo Simple Background Jobs
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class TestServiceJob extends AbstractServiceJob {
    
    /**
     * @abstract    Job Inputs => Load here all inputs parameters for your task
     *      
     * @var array
     */
    protected $inputs      = array(
        "Service"       =>  "Tasking.Sampling.Service",
        "Method"        =>  "DelayTask",
        "Inputs"        =>  array("Delay" => 1)
    );  
    
    /*
     * @abstract Job Display Settings
     */    
    protected $settings   = array(
        "label"                 =>      "Service Job",
        "description"           =>      "Abstract Service Job Base",
        "translation_domain"    =>      False,
        "translation_params"    =>      array()
    );
    
    /**
     * @abstract    Job Token is Used for concurency Management
     *              You can set it directly by overriding this constant 
     *              or by writing an array of parameters to setJobToken()
     * @var string
     */
    protected $token       = "JOB_SERVICE";    
    
}
