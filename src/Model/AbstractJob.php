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

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Token;
use Splash\Tasking\Events\AddEvent;
use Splash\Tasking\Events\InsertEvent;
use Splash\Tasking\Services\TasksManager;

/**
 * Base Class for Background Jobs Definition
 */
abstract class AbstractJob
{
    const TAG = "splash.tasking.job";

    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Action Method Name
     *
     * @var string
     */
    protected static string $action = "execute";

    /**
     * Job Priority
     *
     * @var int
     */
    protected static int $priority = Task::DO_NORMAL;

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected array $settings = array(
        "label" => "Unknown Job Title",
        "description" => "Unknown Job Description",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    /**
     * Job Indexation Key 1
     *
     * @var null|string
     */
    protected ?string $indexKey1 = null;

    /**
     * Job Indexation Key 2
     *
     * @var null|string
     */
    protected ?string $indexKey2 = null;

    /**
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected array $inputs = array();

    /**
     * Job Token is Used for concurrency Management
     * You can set it directly by overriding this constant
     * or by writing an array of parameters to setJobToken()
     *
     * @var null|string
     */
    protected ?string $token = null;

    /**
     * Job Frequency
     * How often (in Minutes) shall this task be executed
     *
     * @var int
     */
    protected int $frequency = 60;

    //==============================================================================
    // Magic Getters & Setters
    //==============================================================================

    /**
     * Magic Property Getter
     *
     * @param string $property
     *
     * @return null|mixed
     */
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->{$property};
        }

        return null;
    }

    /**
     * Magic Property Setter
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     */
    public function __set(string $property, $value): void
    {
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
        }
    }

    //==============================================================================
    // Task Management
    //==============================================================================

    /**
     * Add Tasks in DataBase
     *
     * @return null|AddEvent
     */
    public function add() : ?AddEvent
    {
        return TasksManager::add($this);
    }

    /**
     * Insert Tasks in DataBase
     *
     * @return null|InsertEvent
     */
    public function insert() : ?InsertEvent
    {
        return TasksManager::addNoCheck($this);
    }

    //==============================================================================
    // Task Execution (To Override)
    //==============================================================================

    /**
     * Override this function to validate you Input parameters
     *
     * @return bool
     */
    public function validate() : bool
    {
        return true;
    }

    /**
     * Override this function to prepare your class for it's execution
     *
     * @return bool
     */
    public function prepare() : bool
    {
        return true;
    }

    /**
     * Override this function to perform your task
     *
     * @return bool
     */
    public function execute() : bool
    {
        return true;
    }

    /**
     * Override this function to validate results of your task or perform post-actions
     *
     * @return bool
     */
    public function finalize() : bool
    {
        return true;
    }

    /**
     * Override this function to close your task
     *
     * @return bool
     */
    public function close() : bool
    {
        return true;
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Get Job Action Name
     *
     * @return string
     */
    public function getAction() : string
    {
        return static::$action;
    }

    /**
     * Get Job Priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return static::$priority;
    }

    /**
     * Set Job Token
     *
     * @param null|array|string $token
     *
     * @return $this
     */
    public function setToken($token): self
    {
        //==============================================================================
        // If Token Array => Build Token
        // If Token is String => Direct Token
        $this->token = is_array($token) ? Token::build($token) : $token;

        return $this;
    }

    //==============================================================================
    //      Getters & Setters
    //==============================================================================

    /**
     * Set Job Inputs
     *
     * @param array $inputs
     *
     * @return $this
     */
    public function setInputs(array $inputs): self
    {
        $this->inputs = $inputs;

        return $this;
    }

    /**
     * Get Job Inputs
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get Job Inputs
     *
     * @return array
     *
     * @final Since 5.x
     */
    public function getRawInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get Job Token
     *
     * @return string
     */
    public function getToken(): ?string
    {
        return $this->token ?? null;
    }

    /**
     * Get Job Settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get Job IndexKey1
     *
     * @return null|string
     */
    public function getIndexKey1(): ?string
    {
        return $this->indexKey1 ?? null;
    }

    /**
     * Get Job IndexKey2
     *
     * @return null|string
     */
    public function getIndexKey2(): ?string
    {
        return $this->indexKey2 ?? null;
    }

    /**
     * Set Job Frequency in Minutes
     *
     * @param int $frequency
     *
     * @return $this
     */
    public function setFrequency(int $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get Job Frequency
     *
     * @return int
     */
    public function getFrequency(): int
    {
        return $this->frequency;
    }
}
