<?php

namespace Splash\Tasking\Model;

use Splash\Tasking\Model\AbstractJob;

/**
 * @abstract    Service Action for Background Jobs
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
abstract class AbstractServiceJob extends AbstractJob {
    
    //==============================================================================
    //  Constants Definition           
    //==============================================================================

    /**
     * @abstract    Job Inputs => Load here all inputs parameters for your task
     *      
     * @var array
     */
    protected $inputs      = array(
        "Service"       =>  Null,
        "Method"        =>  Null,
        "Inputs"        =>  array()
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
    
    
//==============================================================================
//      Service Job Execution
//==============================================================================

    /*
     * @abstract    Overide this function to validate you Input parameters
     */    
    public function validate() : bool {
        
        //====================================================================//
        // Check Inputs Are Not Empty
        if( empty($this->getService()) || empty($this->getMethod())) {
            return False;
        }
        
        //====================================================================//
        // Check Service & Method Exists
        if( !$this->container->has($this->getService()) ) {
            throw new \Exception("Service " . $this->getService() . " not found");            
        }
        if( !method_exists($this->container->get($this->getService()) , $this->getMethod()) ) {
            throw new \Exception("Method " . $this->getMethod() . " not found");            
        }
        return True;
    }
    
    /*
     * @abstract    Overide this function to perform your task
     */    
    public function execute() : bool {
        
        //====================================================================//
        // Load Requested Service
        $Service    =   $this->container->get($this->getService());
        $Method     =   $this->getMethod();
        $Inputs     =   $this->getInputs();
        
        //====================================================================//
        // Execute Service Method
        return $Service->{ $Method }($Inputs);
    }

    
//==============================================================================
//      Specific Getters & Setters
//==============================================================================
        
    /**
     * Get Service Job Service Name
     *
     * @return string
     */
    public function getService()
    {
        return isset($this->inputs["Service"]) ? $this->inputs["Service"] : Null;
    }

    /**
     * Set Service Job Service Name
     *
     * @param string $service
     *
     * @return string
     */
    public function setService($service)
    {
        $this->inputs["Service"] = $service;
        
        return $this;
    }

    /**
     * Get Service Job Method Name
     *
     * @return string
     */
    public function getMethod()
    {
        return isset($this->inputs["Method"]) ? $this->inputs["Method"] : Null;
    }

    /**
     * Set Service Job Service Name
     *
     * @param string $method
     *
     * @return AbstractServiceJob
     */
    public function setMethod($method)
    {
        $this->inputs["Method"] = $method;
        
        return $this;
    }
    
    /**
     * Set Job Inputs
     * 
     * @param array $inputs
     *
     * @return AbstractServiceJob
     */
    public function setInputs($inputs)
    {
        $this->inputs["Inputs"] = $inputs;
        
        return $this;
    }     

    /**
     * Get Job Inputs
     *
     * @return array
     */
    public function getInputs()
    {
        if ( isset($this->inputs["Inputs"]) ) {
            return $this->inputs["Inputs"];
        }
        return Null;
    }     
    

    
}
