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
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private $nodeName;

    /**
     * @var string
     *
     * @ORM\Column(name="Ip", type="string", length=250)
     */
    private $nodeIp;

    /**
     * @var string
     *
     * @ORM\Column(name="Infos", type="string", length=512)
     */
    private $nodeInfos;

    /**
     * @var string
     *
     * @ORM\Column(name="Process", type="string", length=250)
     */
    private $process;

    /**
     * @var int
     *
     * @ORM\Column(name="PID", type="integer")
     */
    private $pID;

    /**
     * @var bool
     *
     * @ORM\Column(name="Enabled", type="boolean", nullable=TRUE)
     */
    private $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="Running", type="boolean", nullable=TRUE)
     */
    private $running;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="SeenAt", type="datetime", nullable=TRUE)
     */
    private $lastSeen;

    /**
     * @var string
     *
     * @ORM\Column(name="Task", type="string", length=250, nullable=TRUE)
     */
    private $task;

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
        // Ask for Process Group Id
        $groupId = posix_getpgid($this->getPid());
        //==============================================================================
        // Check if Process Group was Found
        return ($groupId > 0) ? true : false;
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
        if ($system["nodename"] !== $this->getNodeName()) {
            return true;
        }
        //==============================================================================
        // Check if Worker Process is Ok
        return $this->ping();
    }

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
     * @param null|string $nodeIp
     *
     * @return Worker
     */
    public function setNodeIp(?string $nodeIp)
    {
        $this->nodeIp = (null == $nodeIp) ? "127.0.0.1" : $nodeIp;

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
    public function setPid(int $pID)
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
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
