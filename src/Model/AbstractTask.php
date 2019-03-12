<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as ASSERT;

/**
 * Abstract Task Storage Object
 *
 * @ORM\Entity
 * @ORM\MappedSuperclass
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractTask
{
    
    //==============================================================================
    //      Task Display Informations
    //==============================================================================

    /**
     * Task Display Settings
     *
     * @var array
     *
     * @ORM\Column(name="Settings", type="array")
     */
    protected $settings = array();

    /**
     * Static Tasks - Repeat Delay in Minutes
     *
     * @var int
     *
     * @ORM\Column(name="JobFreq", type="integer", nullable=TRUE)
     */
    protected $jobFrequency;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="StartedAt", type="datetime", nullable=TRUE)
     */
    protected $startedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="StartedAtTimeStamp", type="integer", nullable=TRUE)
     */
    protected $startedAtTimeStamp;

    /**
     * @var float
     */
    protected $startedAtMicroTime;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="FinishedAt", type="datetime", nullable=TRUE)
     */
    protected $finishedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="FinishedAtTimeStamp", type="integer", nullable=TRUE)
     */
    protected $finishedAtTimeStamp;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="PlannedAt", type="datetime", nullable=TRUE)
     */
    protected $plannedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="PlannedAtTimeStamp", type="integer", nullable=TRUE)
     */
    protected $plannedAtTimeStamp;
    
    //==============================================================================
    //      Definition
    //==============================================================================

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Task Name (Unused in User HMI, Only for Admin)
     *
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250)
     */
    protected $name;

    //==============================================================================
    //      Task User Parameters
    //==============================================================================

    /**
     * @var string
     *
     * @ORM\Column(name="JobClass", type="string", length=250)
     */
    protected $jobClass;

    /**
     * @var string
     *
     * @ORM\Column(name="JobAction", type="string", length=250)
     */
    protected $jobAction;

    /**
     * @var string
     *
     * @ORM\Column(name="JobPriority", type="string", length=250)
     */
    protected $jobPriority = "5";

    /**
     * @var array
     *
     * @ORM\Column(name="JobInputs", type="array", nullable=TRUE)
     */
    protected $jobInputs = array();

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobToken", type="string", length=250, nullable=TRUE)
     */
    protected $jobToken;

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobIndexKey1", type="string", length=250, nullable=TRUE)
     */
    protected $jobIndexKey1;

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobIndexKey2", type="string", length=250, nullable=TRUE)
     */
    protected $jobIndexKey2;

    /**
     * Set if Job is A Static Job. Defined in configuration
     *
     * @var bool
     *
     * @ORM\Column(name="JobIsStatic", type="boolean", nullable=TRUE)
     */
    protected $jobIsStatic = false;

    //==============================================================================
    //      Status
    //==============================================================================

    /**
     * Count Number of Task Execution Tentatives
     *
     * @var int
     *
     * @ORM\Column(name="NbTry", type="integer", nullable=TRUE)
     *
     * @ASSERT\Range(
     *      min = 0,
     *      max = 10
     * )
     */
    protected $try = 0;

    /**
     * Task is Pending
     *
     * @var bool
     *
     * @ORM\Column(name="Running", type="boolean", nullable=TRUE)
     */
    protected $running = false;

    /**
     * Task is Finished
     *
     * @var bool
     *
     * @ORM\Column(name="Finished", type="boolean", nullable=TRUE)
     */
    protected $finished = false;

    /**
     * @var string
     *
     * @ORM\Column(name="StartedBy", type="string", length=250, nullable=TRUE)
     */
    protected $startedBy;

    /**
     * @abstract    Task Duration in Ms
     *
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=TRUE)
     */
    protected $duration;

    //==============================================================================
    //      Audit
    //==============================================================================

    /**
     * Task Discriminator - Unique Task Identification
     *
     * @var string
     *
     * @ORM\Column(name="Md5", type="string", length=250)
     */
    protected $discriminator;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="CreatedAt", type="datetime")
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="CreatedBy", type="string", length=250)
     */
    protected $createdBy;

    /**
     * @var null|string
     *
     * @ORM\Column(name="Fault", type="text", nullable=TRUE)
     */
    protected $faultStr;

    /**
     * @var string
     *
     * @ORM\Column(name="FaultTrace", type="text", nullable=TRUE)
     */
    protected $faultTrace;

    /**
     * @var string
     *
     * @ORM\Column(name="Outputs", type="text", nullable=TRUE)
     */
    protected $outputs;

    //==============================================================================
    //      Generic Getters & Setters
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
     * Set name
     *
     * @param string $name
     *
     * @return $this
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
     * Get settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set jobClass
     *
     * @param string $jobClass
     *
     * @return $this
     */
    public function setJobClass($jobClass)
    {
        $this->jobClass = $jobClass;

        return $this;
    }

    /**
     * Get jobClass
     *
     * @return string
     */
    public function getJobClass()
    {
        return $this->jobClass;
    }

    /**
     * Set jobAction
     *
     * @param string $jobAction
     *
     * @return $this
     */
    public function setJobAction($jobAction)
    {
        $this->jobAction = $jobAction;

        return $this;
    }

    /**
     * Get jobAction
     *
     * @return string
     */
    public function getJobAction()
    {
        return $this->jobAction;
    }

    /**
     * Set jobPriority
     *
     * @param int $jobPriority
     *
     * @return $this
     */
    public function setJobPriority(int $jobPriority): self
    {
        $this->jobPriority = (string) $jobPriority;

        return $this;
    }

    /**
     * Get jobPriority
     *
     * @return int
     */
    public function getJobPriority(): int
    {
        return (int) $this->jobPriority;
    }

    /**
     * Set jobInputs
     *
     * @param array $jobInputs
     *
     * @return $this
     */
    public function setJobInputs($jobInputs)
    {
        $this->jobInputs = $jobInputs;

        return $this;
    }

    /**
     * Get jobInputs
     *
     * @return array
     */
    public function getJobInputs()
    {
        return $this->jobInputs;
    }

    /**
     * Set jobToken
     *
     * @param string $jobToken
     *
     * @return $this
     */
    public function setJobToken(?string $jobToken): self
    {
        $this->jobToken = $jobToken;

        return $this;
    }

    /**
     * Get jobToken
     *
     * @return null|string
     */
    public function getJobToken(): ?string
    {
        return $this->jobToken;
    }

    /**
     * Set jobIsStatic
     *
     * @param boolean $jobIsStatic
     *
     * @return $this
     */
    public function setJobIsStatic($jobIsStatic)
    {
        $this->jobIsStatic = $jobIsStatic;

        return $this;
    }

    /**
     * Get jobIsStatic
     *
     * @return boolean
     */
    public function isStaticJob()
    {
        return $this->jobIsStatic;
    }

    /**
     * Get jobFrequency
     *
     * @return integer
     */
    public function getJobFrequency()
    {
        return $this->jobFrequency;
    }

    /**
     * Set jobIndexKey1
     *
     * @param null|string $jobIndexKey1
     *
     * @return $this
     */
    public function setJobIndexKey1(?string $jobIndexKey1)
    {
        $this->jobIndexKey1 = $jobIndexKey1;

        return $this;
    }

    /**
     * Get jobIndexKey1
     *
     * @return null|string
     */
    public function getJobIndexKey1(): ?string
    {
        return $this->jobIndexKey1;
    }

    /**
     * Set jobIndexKey2
     *
     * @param null|string $jobIndexKey2
     *
     * @return $this
     */
    public function setJobIndexKey2(?string $jobIndexKey2)
    {
        $this->jobIndexKey2 = $jobIndexKey2;

        return $this;
    }

    /**
     * Get jobIndexKey2
     *
     * @return null|string
     */
    public function getJobIndexKey2(): ?string
    {
        return $this->jobIndexKey2;
    }

    /**
     * Set try
     *
     * @param integer $try
     *
     * @return $this
     */
    public function setTry($try)
    {
        $this->try = $try;

        return $this;
    }

    /**
     * Get try
     *
     * @return integer
     */
    public function getTry()
    {
        return $this->try;
    }

    /**
     * Set running
     *
     * @param boolean $running
     *
     * @return $this
     */
    public function setRunning($running)
    {
        $this->running = $running;

        return $this;
    }

    /**
     * Get running
     *
     * @return boolean
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Set finished
     *
     * @param boolean $finished
     *
     * @return $this
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Get finished
     *
     * @return boolean
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set startedAtTimeStamp
     *
     * @param integer $startedAtTimeStamp
     *
     * @return $this
     */
    public function setStartedAtTimeStamp($startedAtTimeStamp)
    {
        $this->startedAtTimeStamp = $startedAtTimeStamp;

        return $this;
    }

    /**
     * Get startedAtTimeStamp
     *
     * @return integer
     */
    public function getStartedAtTimeStamp()
    {
        return $this->startedAtTimeStamp;
    }

    /**
     * Get finishedAt
     *
     * @return \DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * Set finishedAtTimeStamp
     *
     * @param integer $finishedAtTimeStamp
     *
     * @return $this
     */
    public function setFinishedAtTimeStamp($finishedAtTimeStamp)
    {
        $this->finishedAtTimeStamp = $finishedAtTimeStamp;

        return $this;
    }

    /**
     * Get finishedAtTimeStamp
     *
     * @return integer
     */
    public function getFinishedAtTimeStamp()
    {
        return $this->finishedAtTimeStamp;
    }

    /**
     * Set startedBy
     *
     * @param string $startedBy
     *
     * @return $this
     */
    public function setStartedBy($startedBy)
    {
        $this->startedBy = $startedBy;

        return $this;
    }

    /**
     * Get startedBy
     *
     * @return string
     */
    public function getStartedBy()
    {
        return $this->startedBy;
    }

    /**
     * Get plannedAt
     *
     * @return \DateTime
     */
    public function getPlannedAt()
    {
        return $this->plannedAt;
    }

    /**
     * Get plannedAtTimeStamp
     *
     * @return integer
     */
    public function getPlannedAtTimeStamp()
    {
        return $this->plannedAtTimeStamp;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return $this
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
     * Set createdBy
     *
     * @param string $createdBy
     *
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set faultStr
     *
     * @param null|string          $faultStr
     *
     * @return $this
     */
    public function setFaultStr(?string $faultStr): self
    {
        $this->faultStr = (string) $faultStr;

        return $this;
    }
    
    /**
     * Get faultStr
     *
     * @return null|string
     */
    public function getFaultStr()
    {
        return $this->faultStr;
    }

    /**
     * Set fault Trace
     *
     * @param string $faultTrace
     *
     * @return $this
     */
    public function setFaultTrace($faultTrace)
    {
        $this->faultTrace = $faultTrace;

        return $this;
    }

    /**
     * Get fault Trace
     *
     * @return string
     */
    public function getFaultTrace()
    {
        return $this->faultTrace;
    }

    /**
     * Get Task Outputs
     *
     * @return string
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * Get Task Disciminator
     *
     * @return string
     */
    public function getDiscriminator()
    {
        return $this->discriminator;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }
}
