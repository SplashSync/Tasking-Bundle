<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
 *
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
     * @ORM\Column(name="Settings", type="json")
     */
    protected array $settings = array();

    /**
     * Static Tasks - Repeat Delay in Minutes
     *
     * @var null|int
     *
     * @ORM\Column(name="JobFreq", type="integer", nullable=true)
     */
    protected ?int $jobFrequency;

    /**
     * @var null|DateTime
     *
     * @ORM\Column(name="StartedAt", type="datetime", nullable=true)
     */
    protected ?DateTime $startedAt;

    /**
     * @var null|int
     *
     * @ORM\Column(name="StartedAtTimeStamp", type="integer", nullable=true)
     */
    protected ?int $startedAtTimeStamp;

    /**
     * @var float
     */
    protected float $startedAtMicroTime;

    /**
     * @var null|DateTime
     *
     * @ORM\Column(name="FinishedAt", type="datetime", nullable=true)
     */
    protected ?DateTime $finishedAt;

    /**
     * @var null|int
     *
     * @ORM\Column(name="FinishedAtTimeStamp", type="integer", nullable=true)
     */
    protected ?int $finishedAtTimeStamp;

    /**
     * @var null|DateTime
     *
     * @ORM\Column(name="PlannedAt", type="datetime", nullable=true)
     */
    protected ?DateTime $plannedAt;

    /**
     * @var null|int
     *
     * @ORM\Column(name="PlannedAtTimeStamp", type="integer", nullable=true)
     */
    protected ?int $plannedAtTimeStamp;

    //==============================================================================
    //      Definition
    //==============================================================================

    /**
     * @var null|int
     *
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * Task Name (Unused in User HMI, Only for Admin)
     *
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250)
     */
    protected string $name;

    //==============================================================================
    //      Task User Parameters
    //==============================================================================

    /**
     * @var class-string
     *
     * @ORM\Column(name="JobClass", type="string", length=250)
     */
    protected string $jobClass;

    /**
     * @var string
     *
     * @ORM\Column(name="JobAction", type="string", length=250)
     */
    protected string $jobAction;

    /**
     * @var int
     *
     * @ORM\Column(name="JobPriority", type="integer", length=250)
     */
    protected int $jobPriority = 5;

    /**
     * @var null|array
     *
     * @ORM\Column(name="JobInputs", type="json", nullable=true)
     */
    protected ?array $jobInputs = array();

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobToken", type="string", length=250, nullable=true)
     */
    protected ?string $jobToken;

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobIndexKey1", type="string", length=250, nullable=true)
     */
    protected ?string $jobIndexKey1;

    /**
     * @var null|string
     *
     * @ORM\Column(name="JobIndexKey2", type="string", length=250, nullable=true)
     */
    protected ?string $jobIndexKey2;

    /**
     * Set if Job is A Static Job. Defined in configuration
     *
     * @var null|bool
     *
     * @ORM\Column(name="JobIsStatic", type="boolean", nullable=true)
     */
    protected ?bool $jobIsStatic = false;

    //==============================================================================
    //      Status
    //==============================================================================

    /**
     * Count Number of Task Execution Tentatives
     *
     * @var int
     *
     * @ORM\Column(name="NbTry", type="integer", nullable=true)
     *
     * @ASSERT\Range(
     *      min = 0,
     *      max = 10
     * )
     */
    protected int $try = 0;

    /**
     * Task is Pending
     *
     * @var null|bool
     *
     * @ORM\Column(name="Running", type="boolean", nullable=true)
     */
    protected ?bool $running = false;

    /**
     * Task is Finished
     *
     * @var null|bool
     *
     * @ORM\Column(name="Finished", type="boolean", nullable=true)
     */
    protected ?bool $finished = false;

    /**
     * @var null|string
     *
     * @ORM\Column(name="StartedBy", type="string", length=250, nullable=true)
     */
    protected ?string $startedBy;

    /**
     * Task Duration in Ms
     *
     * @var null|int
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    protected ?int $duration;

    //==============================================================================
    //      Audit
    //==============================================================================

    /**
     * Task Discriminator - Unique Task Identification
     *
     * @var null|string
     *
     * @ORM\Column(name="Md5", type="string", length=250)
     */
    protected ?string $discriminator = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="CreatedAt", type="datetime")
     */
    protected DateTime $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="CreatedBy", type="string", length=250)
     */
    protected string $createdBy;

    /**
     * @var null|string
     *
     * @ORM\Column(name="Fault", type="text", nullable=true)
     */
    protected ?string $faultStr;

    /**
     * @var null|string
     *
     * @ORM\Column(name="FaultTrace", type="text", nullable=true)
     */
    protected ?string $faultTrace;

    /**
     * @var null|string
     *
     * @ORM\Column(name="Outputs", type="text", nullable=true)
     */
    protected ?string $outputs = null;

    //==============================================================================
    //      Generic Getters & Setters
    //==============================================================================

    /**
     * Get ID
     *
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Set jobClass
     *
     * @param class-string $jobClass
     *
     * @return $this
     */
    public function setJobClass(string $jobClass): self
    {
        $this->jobClass = $jobClass;

        return $this;
    }

    /**
     * Get jobClass
     *
     * @return class-string
     */
    public function getJobClass(): string
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
    public function setJobAction(string $jobAction): self
    {
        $this->jobAction = $jobAction;

        return $this;
    }

    /**
     * Get jobAction
     *
     * @return string
     */
    public function getJobAction(): string
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
        $this->jobPriority = $jobPriority;

        return $this;
    }

    /**
     * Get jobPriority
     *
     * @return int
     */
    public function getJobPriority(): int
    {
        return $this->jobPriority ?? 5;
    }

    /**
     * Set jobInputs
     *
     * @param array $jobInputs
     *
     * @return $this
     */
    public function setJobInputs(array $jobInputs): self
    {
        $this->jobInputs = $jobInputs;

        return $this;
    }

    /**
     * Get jobInputs
     *
     * @return array
     */
    public function getJobInputs(): array
    {
        return $this->jobInputs ?? array();
    }

    /**
     * Set jobToken
     *
     * @param null|string $jobToken
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
        return $this->jobToken ?? null;
    }

    /**
     * Set jobIsStatic
     *
     * @param bool $jobIsStatic
     *
     * @return $this
     */
    public function setJobIsStatic(bool $jobIsStatic): self
    {
        $this->jobIsStatic = $jobIsStatic;

        return $this;
    }

    /**
     * Get jobIsStatic
     *
     * @return bool
     */
    public function isStaticJob(): bool
    {
        return $this->jobIsStatic ?? false;
    }

    /**
     * Get jobFrequency
     *
     * @return int
     */
    public function getJobFrequency(): int
    {
        return $this->jobFrequency ?? 0;
    }

    /**
     * Set jobIndexKey1
     *
     * @param null|string $jobIndexKey1
     *
     * @return $this
     */
    public function setJobIndexKey1(?string $jobIndexKey1): self
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
        return $this->jobIndexKey1 ?? null;
    }

    /**
     * Set jobIndexKey2
     *
     * @param null|string $jobIndexKey2
     *
     * @return $this
     */
    public function setJobIndexKey2(?string $jobIndexKey2): self
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
        return $this->jobIndexKey2 ?? null;
    }

    /**
     * Set try
     *
     * @param integer $try
     *
     * @return $this
     */
    public function setTry(int $try): self
    {
        $this->try = $try;

        return $this;
    }

    /**
     * Get try
     *
     * @return int
     */
    public function getTry(): int
    {
        return $this->try ?? 0;
    }

    /**
     * Set running
     *
     * @param boolean $running
     *
     * @return $this
     */
    public function setRunning(bool $running): self
    {
        $this->running = $running;

        return $this;
    }

    /**
     * Get running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running ?? false;
    }

    /**
     * Set finished
     *
     * @param bool $finished
     *
     * @return $this
     */
    public function setFinished(bool $finished): self
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
        return $this->finished ?? false;
    }

    /**
     * Get startedAt
     *
     * @return null|DateTime
     */
    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt ?? null;
    }

    /**
     * Get finishedAt
     *
     * @return null|DateTime
     */
    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt ?? null;
    }

    /**
     * Set startedBy
     *
     * @param string $startedBy
     *
     * @return $this
     */
    public function setStartedBy(string $startedBy): self
    {
        $this->startedBy = $startedBy;

        return $this;
    }

    /**
     * Get startedBy
     *
     * @return null|string
     */
    public function getStartedBy(): ?string
    {
        return $this->startedBy ?? null;
    }

    /**
     * Get plannedAt
     *
     * @return null|DateTime
     */
    public function getPlannedAt(): ?DateTime
    {
        return $this->plannedAt ?? null;
    }

    /**
     * Get plannedAtTimeStamp
     *
     * @return null|int
     */
    public function getPlannedAtTimeStamp(): ?int
    {
        return $this->plannedAtTimeStamp ?? null;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
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
    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string
     */
    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    /**
     * Set faultStr
     *
     * @param null|string $faultStr
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
    public function getFaultStr(): ?string
    {
        return $this->faultStr ?? null;
    }

    /**
     * Set fault Trace
     *
     * @param null|string $faultTrace
     *
     * @return $this
     */
    public function setFaultTrace(?string $faultTrace): self
    {
        $this->faultTrace = $faultTrace;

        return $this;
    }

    /**
     * Get fault Trace
     *
     * @return null|string
     */
    public function getFaultTrace(): ?string
    {
        return $this->faultTrace ?? null;
    }

    /**
     * Get Task Outputs
     *
     * @return null|string
     */
    public function getOutputs(): ?string
    {
        return $this->outputs;
    }

    /**
     * Get Task Discriminator
     *
     * @return null|string
     */
    public function getDiscriminator(): ?string
    {
        return $this->discriminator ?? null;
    }

    /**
     * Set duration
     *
     * @param int $duration
     *
     * @return $this
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration ?? 0;
    }
}
