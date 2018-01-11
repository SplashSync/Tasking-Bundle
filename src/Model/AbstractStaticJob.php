<?php

namespace Splash\Tasking\Model;

use Splash\Tasking\Entity\Task;

/**
 * @abstract    Base Class for Background Jobs Definition
 *
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
abstract class AbstractStaticJob extends AbstractJob {
    
//==============================================================================
//  Constants Definition           
//==============================================================================

    /*
     * @abstract    Job Priority
     * @var int
     */    
    const priority      = Task::DO_LOW;
    
    /**
     * @abstract    Job Frequency => How often (in Seconds) shall this task be executed
     *      
     * @var int
     */
    protected $frequency    = 3600;    
    
//==============================================================================
//      Specific Getters & Setters
//==============================================================================
    
    /**
     * Set Job Frequency
     *
     * @param array $frequency
     *
     * @return Task
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }
    
    /**
     * Get Job Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
    }
        
}
