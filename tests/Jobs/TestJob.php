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

namespace Splash\Tasking\Tests\Jobs;

use Exception;
use Splash\Tasking\Model\AbstractJob;

/**
 * Tests Of Simple Background Jobs
 */
class TestJob extends AbstractJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    protected array $inputs = array(
        // Execute Customs Delays
        "Delay-Ms" => 0,
        "Delay-S" => 1,

        // Simulate Configuration Errors
        "Error-Wrong-Action" => false,
        "Error-Wrong-Priority" => false,
        "Error-On-Validate" => false,
        "Error-On-Prepare" => false,
        "Error-On-Execute" => false,
        "Error-On-Finalize" => false,
        "Error-On-Close" => false,

        // Simulate Exceptions
        "Exception-On-Validate" => false,
        "Exception-On-Prepare" => false,
        "Exception-On-Execute" => false,
        "Exception-On-Finalize" => false,
        "Exception-On-Close" => false,
    );

    /**
     * {@inheritdoc}
     */
    protected ?string $token = "TEST_JOB";

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Simple Job for Test",
        "description" => "Custom Simple Job for Bundle Testing",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * Set Static Job Repeat Delay
     *
     * @param int $delay
     *
     * @return $this
     */
    public function setDelay(int $delay): self
    {
        $this->setInputs(array("delay" => $delay));

        return $this;
    }

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function validate() : bool
    {
        echo "Simple Job => Validate Inputs </br>";
        //====================================================================//
        // Load Inputs Parameters
        $inputs = $this->getInputs();
        //====================================================================//
        // Validate Delay Sec
        if (isset($inputs["Delay-S"]) && !is_integer($inputs["Delay-S"])) {
            echo " => Delay Sec is not a Integer value!</br>";

            return false;
        }
        //====================================================================//
        // Validate Delay Ms
        if (isset($inputs["Delay-Ms"]) && !is_integer($inputs["Delay-Ms"])) {
            echo " => Delay Ms is not a Integer value!</br>";

            return false;
        }
        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Validate");

        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Validate");
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function prepare() : bool
    {
        echo "Simple Job => Prepare for Action </br>";

        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Prepare");

        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Prepare");
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function execute() : bool
    {
        echo "Simple Job => Execute Requted Actions! </br>";

        //====================================================================//
        // Execute Requested Pauses
        $this->doDelays();

        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Execute");

        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Execute");
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function finalize() : bool
    {
        echo "Simple Job => Finalize Action </br>";

        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Finalize");

        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Finalize");
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function close() : bool
    {
        echo "Simple Job => Close Action </br>";

        //====================================================================//
        // Trow exception if requested!
        $this->doThrowException("Close");

        //====================================================================//
        // Return Error if requested!
        return $this->doErrorReturn("Close");
    }

    /**
     * Get Job Action Name
     *
     * @return string
     */
    public function getAction(): string
    {
        //====================================================================//
        // Simulate Wrong Action Name
        if (isset($this->inputs, $this->inputs["Error-Wrong-Action"])
            && (true === $this->inputs["Error-Wrong-Action"])) {
            return "WrongAction";
        }

        return static::$action;
    }

    /**
     * Get Job Priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        //====================================================================//
        // Simulate Wrong Priority Format
        if (isset($this->inputs, $this->inputs["Error-Wrong-Priority"])
            && (true === $this->inputs["Error-Wrong-Priority"])) {
            return -1;
        }

        return static::$priority;
    }

    /**
     * Execute requested Delays
     */
    public function doDelays(): void
    {
        //====================================================================//
        // Milliseconds Delay
        if (isset($this->inputs["Delay-Ms"]) && (true == $this->inputs["Delay-Ms"])) {
            echo "Simple Job => Wait for ".$this->inputs["Delay-Ms"]." Ms </br>";
            usleep((int) 1E3 * $this->inputs["Delay-Ms"]);
        }
        //====================================================================//
        // Seconds Delay
        if (isset($this->inputs["Delay-S"]) && (true == $this->inputs["Delay-S"])) {
            echo "Simple Job => Wait for ".$this->inputs["Delay-S"]." Seconds </br>";
            sleep($this->inputs["Delay-S"]);
        }
    }

    /**
     * Return False (Error) if Requested by User
     *
     * @param string $methodName
     *
     * @return bool
     */
    public function doErrorReturn(string $methodName): bool
    {
        //====================================================================//
        // Compute Input Parameter Index
        $parameterId = "Error-On-".$methodName;
        //====================================================================//
        // Trow exception if requested!
        if (isset($this->inputs[$parameterId]) && (true == $this->inputs[$parameterId])) {
            echo "You requeted Job Error on ".$methodName." Method.";

            return false;
        }

        return true;
    }

    /**
     * Thow an Exception if Requested by User
     *
     * @param string $methodName
     *
     * @throws Exception
     */
    public function doThrowException(string $methodName): void
    {
        //====================================================================//
        // Compute Input Parameter Index
        $parameterId = "Exception-On-".$methodName;
        //====================================================================//
        // Trow exception if requested!
        if (isset($this->inputs[$parameterId]) && (true == $this->inputs[$parameterId])) {
            throw new Exception(sprintf("You requested Job to Fail on %s Method.", $methodName));
        }
    }
}
