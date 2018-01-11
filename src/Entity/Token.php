<?php

namespace Splash\Tasking\Entity;

use DateTime;
use DateInterval;
use Doctrine\ORM\Mapping as ORM;

/**
 * System Global DBAL Task Token
 * Used to prevent task collisions  
 * 
 * @ORM\Entity(repositoryClass="Splash\Tasking\Repository\TokenRepository")
 * @ORM\Table(name="system__tokens")
 * @ORM\HasLifecycleCallbacks
 * 
 */

class Token
{
    
//==============================================================================
//  Constants Definition           
//==============================================================================
 
    /*
     *  Token Parameters
     */    
    const SELFRELEASE_DELAY     = 360;      // Token Validity Delay in Seconds
    const DB_LOCKED_DELAY       = 1;        // Delay Between two DB Write Tentatives in Milliseconds
    const DELETE_DELAY          = 200;      // Token Maximum Inactivity Time in Hours
    
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

//==============================================================================
//      Token Informations           

    /**
     * Token identifier name
     * 
     * @var string
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private $name;

    /**
     * Is This Token in Use
     * 
     * @var boolean
     * @ORM\Column(name="Locked", type="boolean")
     */
    private $locked = False;

    /**
     * When this token was taken
     * 
     * @var \DateTime
     * @ORM\Column(name="LockedAt", type="datetime", nullable=TRUE)
     */
    private $lockedAt = NUll;

    /**
     * When this token was taken as TimeStamp
     * 
     * @var integer
     * @ORM\Column(name="LockedAtTimeStamp", type="integer", nullable=TRUE)
     */
    private $lockedAtTimeStamp = Null;    

    /**
     * @var string
     * @ORM\Column(name="LockedBy", type="string", length=250, nullable=TRUE)
     */
    private $lockedBy;

//==============================================================================
//      Audit           
    
    /**
     * @var \DateTime
     * @ORM\Column(name="CreatedAt", type="datetime")
     */
    private $createdAt;
    
    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @var string
     */
    private $condition;
    
//==============================================================================
//      Object Operations
//==============================================================================

    /**
     * Token Constructor
     * 
     */
    function __construct($Name) {
        $this->setName($Name);
    }
    
    /**
     * Get Token Availability
     *
     * @return bool
     */
    public function isFree()
    {
        return !$this->isLocked();
    }
    
    /**
     * Get Token Availability
     *
     * @return bool
     */
    public function isLocked()
    {
        //====================================================================//
        // Verify if Token already in use
        if ($this->locked == False) {
            return False;
        }
        
        //====================================================================//
        // Verify if Token Validity
        $MaxAge = new \DateTime("-" . self::SELFRELEASE_DELAY . " Seconds");
        return ($this->lockedAt > $MaxAge)?True:False;
    }
    
    /**
     * Lock Token
     *
     * @return string
     */
    public function Acquire($LockedBy = Null)
    {
        //====================================================================//
        // If Token already in use, exit
        if ($this->isLocked()) {
            return False;
        }
        //====================================================================//
        // If LockedBy is Null, Use Machine Name
        if (!$LockedBy) {
            $MachineInfos   =   posix_uname(); 
            $LockedBy       =   $MachineInfos['nodename'];
        }
        //====================================================================//
        // Set This Token as Used
        $this->setLocked(True);
        $this->setLockedAt(new \DateTime);
        $this->setLockedBy($LockedBy);
        
        return True;
    }
    
    /**
     * Lock Token
     *
     * @return string
     */
    public function Release()
    {
        //====================================================================//
        // If Token not in use, exit
        if (!$this->locked) {
            return True;
        }
        
        //====================================================================//
        // Set This Token as NOT Used
        $this->setLocked(False);
        
        return True;
    }    
    
    /**
     *      @abstract    Build Token Key Name from an Array of Parameters
     * 
     *      @param       array    $TokenArray     Token Parameters Given As Array 
     */    
    public static function Build($TokenArray = Null) {

        //==============================================================================
        // If No Token Arrray Given => Exit
        if ( is_null($TokenArray) || !is_array($TokenArray) ) {
            return "None";
        }
        
        //==============================================================================
        // Build Token Key Name
        return implode(":", $TokenArray);
    }      
    
//==============================================================================
//      LifeCycle Events
//==============================================================================
    
    
    /** @ORM\PrePersist() */    
    public function prePersist()
    {
        //====================================================================//
        // Set Created Date
        $this->setCreatedAt(new \DateTime);
    } 
    
//==============================================================================
//      Getters & Setters
//==============================================================================
    

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
     * Set locked
     *
     * @param boolean $locked
     *
     * @return Token
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set lockedAt
     *
     * @param \DateTime $lockedAt
     *
     * @return Token
     */
    public function setLockedAt($lockedAt)
    {
        //====================================================================//
        // Store date as DateTime
        $this->lockedAt             = $lockedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->lockedAtTimeStamp    = $lockedAt->getTimestamp();

        return $this;
    }

    /**
     * Get lockedAt
     *
     * @return \DateTime
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    /**
     * Set lockedBy
     *
     * @param string $lockedBy
     *
     * @return Token
     */
    public function setLockedBy($lockedBy)
    {
        $this->lockedBy = $lockedBy;

        return $this;
    }

    /**
     * Get lockedBy
     *
     * @return string
     */
    public function getLockedBy()
    {
        return $this->lockedBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Token
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Token
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lockedAtTimeStamp
     *
     * @param integer $lockedAtTimeStamp
     *
     * @return Token
     */
    public function setLockedAtTimeStamp($lockedAtTimeStamp)
    {
        $this->lockedAtTimeStamp = $lockedAtTimeStamp;

        return $this;
    }

    /**
     * Get lockedAtTimeStamp
     *
     * @return integer
     */
    public function getLockedAtTimeStamp()
    {
        return $this->lockedAtTimeStamp;
    }

    /**
     * Set version
     *
     * @param integer $version
     *
     * @return Token
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set condition
     *
     * @param string $condition
     *
     * @return Token
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
