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

namespace Splash\Tasking\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * System Task Worker Tracker
 *
 * @ORM\Entity(repositoryClass="Splash\Tasking\Repository\WorkerRepository")
 * @ORM\Table(name="system__workers")
 * @ORM\HasLifecycleCallbacks
 */
class Worker
{
    //==============================================================================
    //      Definition
    //==============================================================================

    /**
     * @var null|int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private string $nodeName;

    /**
     * @var string
     *
     * @ORM\Column(name="Ip", type="string", length=250)
     */
    private string $nodeIp;

    /**
     * @var string
     *
     * @ORM\Column(name="Infos", type="string", length=512)
     */
    private string $nodeInfos;

    /**
     * @var string
     *
     * @ORM\Column(name="Process", type="string", length=250)
     */
    private string $process;

    /**
     * @var int
     *
     * @ORM\Column(name="PID", type="integer")
     */
    private int $pID;

    /**
     * @var null|bool
     *
     * @ORM\Column(name="Enabled", type="boolean", nullable=true)
     */
    private ?bool $enabled = true;

    /**
     * @var null|bool
     *
     * @ORM\Column(name="Running", type="boolean", nullable=true)
     */
    private ?bool $running = false;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="SeenAt", type="datetime", nullable=false)
     */
    private DateTime $lastSeen;

    /**
     * @var null|string
     *
     * @ORM\Column(name="Task", type="string", length=250, nullable=true)
     */
    private ?string $task;

    //==============================================================================
    //      Getters & Setters
    //==============================================================================

    /**
     * Get Worker Name
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->nodeName." [".$this->process."]";
    }

    //==============================================================================
    //      Object Operations
    //==============================================================================

    /**
     * Verify if a Worker Process is Action
     *
     * @return bool
     */
    public function ping(): bool
    {
        //==============================================================================
        // Ask for Process Group ID
        $groupId = posix_getpgid($this->getPid());
        //==============================================================================
        // Check if Process Group was Found
        return $groupId > 0;
    }

    /**
     * Get running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        //==============================================================================
        // Check if Worker is Flagged As Running
        if (!$this->running) {
            return false;
        }
        //==============================================================================
        // Check if Worker WatchDog is Ok
        $limit = new DateTime("-30 Seconds");
        if ($this->getLastSeen() < $limit) {
            return false;
        }
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //==============================================================================
        // If We Are NOT on Worker Real Machine
        if (!$system || ($system["nodename"] !== $this->getNodeName())) {
            return true;
        }
        //==============================================================================
        // Check if Worker Process is Ok
        return $this->ping();
    }

    /**
     * Get id
     *
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * Set nodeName
     *
     * @param string $nodeName
     *
     * @return Worker
     */
    public function setNodeName(string $nodeName): self
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * Get nodeName
     *
     * @return string
     */
    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    /**
     * Set nodeIp
     *
     * @param null|string $nodeIp
     *
     * @return Worker
     */
    public function setNodeIp(?string $nodeIp): self
    {
        $this->nodeIp = (null == $nodeIp) ? "127.0.0.1" : $nodeIp;

        return $this;
    }

    /**
     * Get nodeIp
     *
     * @return string
     */
    public function getNodeIp(): string
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
    public function setNodeInfos(string $nodeInfos): self
    {
        $this->nodeInfos = $nodeInfos;

        return $this;
    }

    /**
     * Get nodeInfos
     *
     * @return string
     */
    public function getNodeInfos(): string
    {
        return $this->nodeInfos;
    }

    /**
     * Set process
     *
     * @param int $process
     *
     * @return $this
     */
    public function setProcess(int $process): self
    {
        $this->process = (string) $process;

        return $this;
    }

    /**
     * Get process
     *
     * @return int
     */
    public function getProcess(): int
    {
        return (int) $this->process;
    }

    /**
     * Set pID
     *
     * @param integer $pID
     *
     * @return Worker
     */
    public function setPid(int $pID): self
    {
        $this->pID = $pID;

        return $this;
    }

    /**
     * Get pID
     *
     * @return integer
     */
    public function getPid(): int
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
    public function setRunning(bool $running): self
    {
        $this->running = $running;

        return $this;
    }

    /**
     * Set lastSeen
     *
     * @param DateTime $lastSeen
     *
     * @return Worker
     */
    public function setLastSeen(DateTime $lastSeen): self
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     * Get lastSeen
     *
     * @return DateTime
     */
    public function getLastSeen(): DateTime
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
    public function setTask(string $task): self
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return string
     */
    public function getTask(): ?string
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
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get Worker is Enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled ?? false;
    }
}
