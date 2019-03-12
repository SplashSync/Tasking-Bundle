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
use Splash\Tasking\Model\AbstractTask;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Splash Task Storage Object
 *
 * @ORM\Entity(repositoryClass="Splash\Tasking\Repository\TaskRepository")
 * @ORM\Table(name="system__tasks")
 * @ORM\HasLifecycleCallbacks
 */
class Task extends AbstractTask
{

    //==============================================================================
    //      Task Priority
    //==============================================================================

    /** @var int */
    const DO_HIGHEST = 10;
    /** @var int */
    const DO_HIGH = 7;
    /** @var int */
    const DO_NORMAL = 5;
    /** @var int */
    const DO_LOW = 3;
    /** @var int */
    const DO_LOWEST = 1;

    //==============================================================================
    //      Crontab Status
    //==============================================================================

    /** @var string */
    const CRONTAB_OK = "Crontab Configuration Already Done";
    /** @var string */
    const CRONTAB_DISABLED = "Crontab Management is Disabled";
    /** @var string */
    const CRONTAB_UPDATED = "Crontab Configuration Updated";

    // Task Settings
    private static $defaultSettings = array(
        "label" => "Default Task Title",
        "description" => "Default Task Description",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    /**
     * Class Constructor
     */
    public function __construct()
    {
        $this->setSettings(static::$defaultSettings);
    }

    //==============================================================================
    //      LifeCycle Events
    //==============================================================================

    /** @ORM\PrePersist() */
    public function prePersist()
    {
        //====================================================================//
        // Set Created Date
        $this->setCreatedAt(new DateTime);
        //====================================================================//
        // Set Created By
        $this->setCreatedBy($this->getCurrentServer());
        //====================================================================//
        // Update Task Discriminator
        $this->updateDiscriminator();
    }

    /** @ORM\PreUpdate() */
    public function preUpdate()
    {
        //====================================================================//
        // Update Task Discriminator
        $this->updateDiscriminator();
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Current Server Identifier
     *
     * @return string
     */
    public static function getCurrentServer(): string
    {
        //====================================================================//
        // Load Current Server Infos
        $system = posix_uname();
        //====================================================================//
        // Return machine Name
        return $system["nodename"];
    }

    /**
     * Get Task Delay in Seconds
     *
     * @return int
     */
    public function getDelay(): int
    {
        if (empty($this->finishedAtTimeStamp) || empty($this->startedAtTimeStamp)) {
            return 0;
        }

        return ($this->finishedAtTimeStamp - $this->startedAtTimeStamp);
    }

    /**
     * Set setting
     *
     * @param string $domain
     * @param mixed  $value
     *
     * @return $this
     */
    public function setSetting(string $domain, $value): self
    {
        //==============================================================================
        // Read Full Settings Array
        $settings = $this->getSettings();
        //==============================================================================
        // Update Domain Setting
        $settings[$domain] = $value;
        //==============================================================================
        // Update Full Settings Array
        $this->setSettings($settings);

        return $this;
    }

    /**
     * Set settings
     *
     * @param array $settings
     *
     * @return $this
     */
    public function setSettings(array $settings): self
    {
        $this->settings = $settings;
        //==============================================================================
        //  Init Settings Array using OptionResolver
        $resolver = (new OptionsResolver())->setDefaults(static::$defaultSettings);
        //==============================================================================
        //  Update Settings Array using OptionResolver
        try {
            $this->settings = $resolver->resolve($settings);
            //==============================================================================
        //  Invalid Field Definition Array
        } catch (UndefinedOptionsException $ex) {
            $this->settings = static::$defaultSettings;
        } catch (InvalidOptionsException $ex) {
            $this->settings = static::$defaultSettings;
        }

        return $this;
    }

    /**
     * Append Task Outputs
     *
     * @param string $text
     *
     * @return string
     */
    public function appendOutputs(string $text): string
    {
        return $this->outputs .= $text.PHP_EOL;
    }

    /**
     * Get jobInputs as a string
     *
     * @return string
     */
    public function getJobInputsStr() : string
    {
        return "<PRE>".print_r($this->jobInputs, true)."</PRE>";
    }

    /**
     * Update Task Disciminator
     *
     * @return $this
     */
    public function updateDiscriminator(): self
    {
        //====================================================================//
        // Prepare Discrimination Array
        $array = array(
            $this->getJobClass(),
            $this->getJobAction(),
            $this->getJobInputs(),
            $this->getSettings(),
        );
        //====================================================================//
        // Setup Discriminator
        $this->discriminator = md5(serialize($array));

        return $this;
    }

    /**
     * Set jobFrequency
     *
     * @param integer $jobFrequency
     *
     * @return $this
     */
    public function setJobFrequency(int $jobFrequency): self
    {
        //====================================================================//
        // Update Job Frequency
        $this->jobFrequency = $jobFrequency;
        //====================================================================//
        // Setup Next Planned Start
        $nextStart = $this->isFinished()
                ? new DateTime("+".$this->getJobFrequency()."Minutes ")
                : new DateTime();
        $this->setPlannedAt($nextStart);

        return $this;
    }
    
    /**
     * Set startedAt
     *
     * @param DateTime $startedAt
     *
     * @return $this
     */
    public function setStartedAt(DateTime $startedAt = null): self
    {
        if (is_null($startedAt)) {
            $startedAt = new DateTime();
        }
        //====================================================================//
        // Store date as DateTime
        $this->startedAt = $startedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->startedAtTimeStamp = $startedAt->getTimestamp();
        //====================================================================//
        // Store date as MicroTime
        $this->startedAtMicroTime = microtime(true);

        return $this;
    }    
    
    /**
     * Set finishedAt
     *
     * @param DateTime $finishedAt
     *
     * @return $this
     */
    public function setFinishedAt(DateTime $finishedAt = null): self
    {
        if (is_null($finishedAt)) {
            $finishedAt = new DateTime();
        }
        //====================================================================//
        // Store date as DateTime
        $this->finishedAt = $finishedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->finishedAtTimeStamp = $finishedAt->getTimestamp();

        //====================================================================//
        // Store Task Duration
        if ($this->startedAtMicroTime) {
            $this->setDuration((int) (1E3 * (microtime(true) - $this->startedAtMicroTime)));
        }

        return $this;
    }
    
    /**
     * Set plannedAt
     *
     * @param DateTime $plannedAt
     *
     * @return $this
     */
    private function setPlannedAt(DateTime $plannedAt): self
    {
        //====================================================================//
        // Store date as DateTime
        $this->plannedAt = $plannedAt;
        //====================================================================//
        // Store date as TimeStamp
        $this->plannedAtTimeStamp = $plannedAt->getTimestamp();

        return $this;
    }
    
}
