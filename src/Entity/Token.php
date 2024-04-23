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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Splash\Tasking\Repository\TokenRepository;
use Splash\Tasking\Services\Configuration;

/**
 * System Global DBAL Task Token
 * Used to prevent task collisions
 */
#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\Table("system__tokens")]
#[ORM\HasLifecycleCallbacks]
class Token
{
    //==============================================================================
    //      Definition
    //==============================================================================

    /**
     * Entity ID
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    //==============================================================================
    //      Token Information
    //==============================================================================

    /**
     * Token identifier name
     */
    #[ORM\Column(name: "Name", type: Types::STRING, length: 250, unique: true)]
    private string $name;

    /**
     * Is This Token in Use
     */
    #[ORM\Column(name: "Locked", type: Types::BOOLEAN)]
    private bool $locked = false;

    /**
     * When this token was taken
     */
    #[ORM\Column(name: "LockedAt", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $lockedAt = null;

    /**
     * When this token was taken as TimeStamp
     */
    #[ORM\Column(name: "LockedAtTimeStamp", type: Types::INTEGER, nullable: true)]
    private ?int $lockedAtTimeStamp = null;

    /**
     * Who Locked this Token ?? Worker Name
     */
    #[ORM\Column(name: "LockedBy", type: Types::STRING, length: 250, nullable: true)]
    private ?string $lockedBy = null;

    //==============================================================================
    //      Audit

    /**
     * Date Token was Created
     */
    #[ORM\Column(name: "CreatedAt", type: Types::DATETIME_MUTABLE)]
    private DateTime $createdAt;

    /**
     * Token Version
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Version]
    private int $version;

    /**
     * @var string
     */
    private string $condition;

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
     * @return bool
     */
    public function isLocked(): bool
    {
        //====================================================================//
        // Verify if Token already in use
        if (!$this->locked) {
            return false;
        }

        //====================================================================//
        // Verify if Token Validity
        try {
            $maxAge = new DateTime("-".Configuration::getTokenSelfReleaseDelay()." Seconds");
        } catch (Exception $e) {
            return false;
        }

        return $this->lockedAt > $maxAge;
    }

    /**
     * Lock Token
     *
     * @param null|string $lockedBy Name of the Machine who Lock
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

    #[ORM\PrePersist]
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
     * Get Id
     *
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
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
     * @return null|int
     */
    public function getLockedAtTimeStamp(): ?int
    {
        return $this->lockedAtTimeStamp ?? null;
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
    public function setCondition(string $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }
}
