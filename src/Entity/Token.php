<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
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
use Exception;
use Splash\Tasking\Services\Configuration;

/**
 * System Global DBAL Task Token
 * Used to prevent task collisions
 *
 * @ORM\Entity(repositoryClass="Splash\Tasking\Repository\TokenRepository")
 * @ORM\Table(name="system__tokens")
 * @ORM\HasLifecycleCallbacks
 */
class Token
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Token Maximum Inactivity Time in Hours
     *
     * @var int
     */
    const DELETE_DELAY = 200;

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

    //==============================================================================
    //      Token Informations

    /**
     * Token identifier name
     *
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250)
     */
    private $name;

    /**
     * Is This Token in Use
     *
     * @var bool
     *
     * @ORM\Column(name="Locked", type="boolean")
     */
    private $locked = false;

    /**
     * When this token was taken
     *
     * @var null|DateTime
     *
     * @ORM\Column(name="LockedAt", type="datetime", nullable=TRUE)
     */
    private $lockedAt;

    /**
     * When this token was taken as TimeStamp
     *
     * @var int
     *
     * @ORM\Column(name="LockedAtTimeStamp", type="integer", nullable=TRUE)
     */
    private $lockedAtTimeStamp;

    /**
     * @var null|string
     *
     * @ORM\Column(name="LockedBy", type="string", length=250, nullable=TRUE)
     */
    private $lockedBy;

    //==============================================================================
    //      Audit

    /**
     * @var DateTime
     *
     * @ORM\Column(name="CreatedAt", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     *
     * @var int
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
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Get Token Availability
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return !$this->isLocked();
    }

    /**
     * Get Token Availability
     *
     * @throws Exception
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        //====================================================================//
        // Verify if Token already in use
        if (false == $this->locked) {
            return false;
        }

        //====================================================================//
        // Verify if Token Validity
        $maxAge = new DateTime("-".Configuration::getTokenSelfReleaseDelay()." Seconds");

        return $this->lockedAt > $maxAge;
    }

    /**
     * Lock Token
     *
     * @param null|string $lockedBy Name of the Machine who Lock
     *
     * @throws Exception
     *
     * @return bool
     */
    public function acquire(string $lockedBy = null): bool
    {
        //====================================================================//
        // If Token already in use, exit
        if ($this->isLocked()) {
            return false;
        }
        //====================================================================//
        // If LockedBy is Null, Use Machine Name
        if (null == $lockedBy) {
            $system = posix_uname();
            $lockedBy = is_array($system) ? $system['nodename'] : "Unknown";
        }
        //====================================================================//
        // Set This Token as Used
        $this->setLocked(true);
        $this->setLockedAt(new DateTime());
        $this->setLockedBy($lockedBy);

        return true;
    }

    /**
     * Lock Token
     *
     * @return bool
     */
    public function release() : bool
    {
        //====================================================================//
        // If Token not in use, exit
        if (!$this->locked) {
            return true;
        }

        //====================================================================//
        // Set This Token as NOT Used
        $this->setLocked(false);

        return true;
    }

    /**
     * Build Token Key Name from an Array of Parameters
     *
     * @param null|array $tokenArray Token Parameters Given As Array
     *
     * @return string
     */
    public static function build(?array $tokenArray): string
    {
        //==============================================================================
        // If No Token Array Given => Exit
        if (is_null($tokenArray)) {
            return "None";
        }

        //==============================================================================
        // Build Token Key Name
        return implode(":", $tokenArray);
    }

    //==============================================================================
    //      LifeCycle Events
    //==============================================================================

    /** @ORM\PrePersist() */
    public function prePersist(): void
    {
        //====================================================================//
        // Set Created Date
        $this->setCreatedAt(new DateTime());
    }

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
     * Set locked
     *
     * @param bool $locked
     *
     * @return $this
     */
    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Set lockedAt
     *
     * @param DateTime $lockedAt
     *
     * @return $this
     */
    public function setLockedAt(DateTime $lockedAt): self
    {
        //====================================================================//
        // Store date as DateTime
        $this->lockedAt = $lockedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->lockedAtTimeStamp = $lockedAt->getTimestamp();

        return $this;
    }

    /**
     * Get lockedAt
     *
     * @return null|DateTime
     */
    public function getLockedAt(): ?DateTime
    {
        return $this->lockedAt;
    }

    /**
     * Set lockedBy
     *
     * @param string $lockedBy
     *
     * @return $this
     */
    public function setLockedBy(string $lockedBy): self
    {
        $this->lockedBy = $lockedBy;

        return $this;
    }

    /**
     * Get lockedBy
     *
     * @return null|string
     */
    public function getLockedBy(): ?string
    {
        return $this->lockedBy;
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
     * Set lockedAtTimeStamp
     *
     * @param integer $lockedAtTimeStamp
     *
     * @return $this
     */
    public function setLockedAtTimeStamp(int $lockedAtTimeStamp): self
    {
        $this->lockedAtTimeStamp = $lockedAtTimeStamp;

        return $this;
    }

    /**
     * Get lockedAtTimeStamp
     *
     * @return integer
     */
    public function getLockedAtTimeStamp(): int
    {
        return $this->lockedAtTimeStamp;
    }

    /**
     * Set version
     *
     * @param integer $version
     *
     * @return $this
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion(): int
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
    public function setCondition($condition): self
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
