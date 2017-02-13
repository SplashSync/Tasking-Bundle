<?php

namespace Splash\Tasking\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * System Task Worker Tracker
 * 
 * @ORM\Entity
 * @ORM\Table(name="system__workers")
 * @ORM\HasLifecycleCallbacks
 * 
 */

class Worker
{
    
//==============================================================================
//  Constants Definition           
//==============================================================================
    
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
     * @var string
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private $nodeName;

    /**
     * @var string
     * @ORM\Column(name="Ip", type="string", length=250)
     */
    private $nodeIp;
    
    /**
     * @var string
     * @ORM\Column(name="Infos", type="string", length=512)
     */
    private $nodeInfos;

    /**
     * @var string
     * @ORM\Column(name="Process", type="string", length=250)
     */
    private $process;

    /**
     * @var integer
     * @ORM\Column(name="PID", type="integer")
     */
    private $pID;


    /**
     * @var boolean
     * @ORM\Column(name="Enabled", type="boolean", nullable=TRUE)
     */
    private $enabled = True;
    
    /**
     * @var boolean
     * @ORM\Column(name="Running", type="boolean", nullable=TRUE)
     */
    private $running;

    /**
     * @var \DateTime
     * @ORM\Column(name="SeenAt", type="datetime", nullable=TRUE)
     */
    private $lastSeen;

    /**
     * @var string
     * @ORM\Column(name="Task", type="string", length=250, nullable=TRUE)
     */
    private $task;

    
//==============================================================================
//      Object Operations
//==============================================================================

    /**
     *      @abstract    Verify if a Worker Process is Action
     */    
    public function Ping() {
        //==============================================================================
        // Check if Process is active
        return posix_getpgid($this->getPID())?True:False;        
    }
        
    /**
     * Get running
     *
     * @return boolean
     */
    public function getRunning()
    {
        //==============================================================================
        // Check if Worker is Flagged As Running
        if (!$this->running) {
            return False;
        } 
        
        //==============================================================================
        // Check if Worker WatchDog is Ok
        $Limit = new \DateTime("-30 Seconds");
        if ($this->getLastSeen() < $Limit) {
            return False;
        }

        //==============================================================================
        // Check if Worker Process is Ok
        return $this->Ping();
    }    
    
//==============================================================================
//      Getters & Setters
//==============================================================================
  
    /**
     * Get Worker Name
     * 
     * @return string
     */
    public function _toString()
    {
        return $this->nodeName . " [" . $this->process . "]";
    }
    
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
     * Set nodeName
     *
     * @param string $nodeName
     *
     * @return Worker
     */
    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * Get nodeName
     *
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * Set nodeIp
     *
     * @param string $nodeIp
     *
     * @return Worker
     */
    public function setNodeIp($nodeIp)
    {
        $this->nodeIp = $nodeIp?$nodeIp:"127.0.0.1";

        return $this;
    }

    /**
     * Get nodeIp
     *
     * @return string
     */
    public function getNodeIp()
    {
        return $this->nodeIp;
    }
    
    /**
     * Set nodeInfos
     *
     * @param string $nodeInfos
     *
     * @return Worker
     */
    public function setNodeInfos($nodeInfos)
    {
        $this->nodeInfos = $nodeInfos;

        return $this;
    }

    /**
     * Get nodeInfos
     *
     * @return string
     */
    public function getNodeInfos()
    {
        return $this->nodeInfos;
    }

    /**
     * Set process
     *
     * @param string $process
     *
     * @return Worker
     */
    public function setProcess($process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Get process
     *
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * Set pID
     *
     * @param integer $pID
     *
     * @return Worker
     */
    public function setPID($pID)
    {
        $this->pID = $pID;

        return $this;
    }

    /**
     * Get pID
     *
     * @return integer
     */
    public function getPID()
    {
        return $this->pID;
    }

    /**
     * Set running
     *
     * @param boolean $running
     *
     * @return Worker
     */
    public function setRunning($running)
    {
        $this->running = $running;

        return $this;
    }

    /**
     * Set lastSeen
     *
     * @param \DateTime $lastSeen
     *
     * @return Worker
     */
    public function setLastSeen($lastSeen)
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     * Get lastSeen
     *
     * @return \DateTime
     */
    public function getLastSeen()
    {
        return $this->lastSeen;
    }
    
    /**
     * Set task
     *
     * @param string $task
     *
     * @return Worker
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }    
    
    /**
     * Set Worker as Enabled
     *
     * @param bool $enabled
     *
     * @return Worker
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get Worker is Enabled
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }    
}

