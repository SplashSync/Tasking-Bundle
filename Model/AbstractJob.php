<?php

namespace Splash\Tasking\Model;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;

use Symfony\Component\EventDispatcher\GenericEvent;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @abstract    Base Class for Background Jobs Definition
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
abstract class AbstractJob extends GenericEvent implements ContainerAwareInterface {
    
    use ContainerAwareTrait;
    
    //==============================================================================
    //  Constants Definition           
    //==============================================================================

    /*
     * @abstract    Job Action Method Name
     * @var string
     */    
    const action        = "execute";

    /*
     * @abstract    Job Priority
     * @var int
     */    
    const priority      = Task::DO_NORMAL;

    /*
     * @abstract Job Display Settings
     */    
    protected $settings   = array(
        "label"                 =>      "Unknown Job Title",
        "description"           =>      "Unknown Job Description",
        "translation_domain"    =>      False,
        "translation_params"    =>      array()
    );
    
    /*
     * @abstract    Job Indexation Key 1
     * @var string
     */    
    protected $indexKey1       = Null;

    /*
     * @abstract    Job Indexation Key 2
     * @var string
     */    
    protected $indexKey2       = Null;
    
    /**
     * @abstract    Job Inputs => Load here all inputs parameters for your task
     *      
     * @var array
     */
    protected $inputs      = array();    
    
    
    /**
     * @abstract    Job Token is Used for concurency Management
     *              You can set it directly by overriding this constant 
     *              or by writing an array of parameters to setJobToken()
     * @var string
     */
    protected $token       = Null;    
    
//==============================================================================
//      Task Execution Management
//==============================================================================

    /*
     * @abstract    Overide this function to validate you Input parameters
     */    
    public function validate() : bool {
        return True;
    }
    
    /*
     * @abstract    Overide this function to prepare your class for it's execution
     */    
    public function prepare() : bool  {
        return True;
    }
    
    /*
     * @abstract    Overide this function to perform your task
     */    
    public function execute() : bool {
        return True;
    }
    
    /*
     * @abstract    Overide this function to validate results of your task or perform post-actions
     */    
    public function finalize() : bool {
        return True;
    }
    
    /*
     * @abstract Overide this function to close your task
     */    
    public function close() : bool  {
        return True;
    }
    
//==============================================================================
//      Specific Getters & Setters
//==============================================================================
        
    /**
     * Get Job Action Name
     *
     * @return string
     */
    public function getAction()
    {
        return static::action;
    }
    
    /**
     * Get Job Priority
     *
     * @return int
     */
    public function getPriority()
    {
        return static::priority;
    }
    
    /**
     * Set Job Token
     *
     * @param string $token
     *
     * @return Task
     */
    public function setToken($token)
    {
        //==============================================================================
        // If Token Array => Build Token
        if ( is_array($token) ) {
            $this->token = Token::build($token);
        } else {
            $this->token = $token;
        }
        return $this;
    }
    
    
    
//==============================================================================
//      Getters & Setters
//==============================================================================
    
    /**
     * Set Job Inputs
     *
     * @param array $inputs
     *
     * @return Task
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;

        return $this;
    }
    
    /**
     * Get Job Inputs
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * Get Job Token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get Job Settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }    
    

    /**
     * Get Job IndexKey1
     *
     * @return string
     */
    public function getIndexKey1()
    {
        return $this->indexKey1;
    }
 
    /**
     * Get Job IndexKey2
     *
     * @return string
     */
    public function getIndexKey2()
    {
        return $this->indexKey2;
    }   
    
    public function __get($property)
    {
        if(property_exists($this, $property)){
            return $this->$property;
        }
        return Null;
    }
    
    public function __set($property,$value)
    {
        if(property_exists($this, $property)){
            $this->$property = $value;
        }
        return $this;
    }
}
