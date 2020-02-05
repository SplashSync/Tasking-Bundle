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

namespace Splash\Tasking\Tests\Jobs;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\AbstractStaticJob;

/**
 * Demonstration fo Simple Background Jobs
 */
class TestStaticJob extends AbstractStaticJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Priority
     *
     * @var int
     */
    protected static $priority = Task::DO_LOWEST;

    /**
     * Job Inputs => Load here all inputs parameters for your task
     *
     * @var array
     */
    protected $inputs = array("delay" => 1);

    /**
     * Job Token is Used for concurency Management
     * You can set it directly by overriding this constant
     * or by writing an array of parameters to setJobToken()
     *
     * @var string
     */
    protected $token = "JOB_STATIC";

    /**
     * Job Frequency => How often (in Seconds) shall this task be executed
     *
     * @var int
     */
    protected $frequency = 10;

    /**
     * Job Display Settings
     *
     * @var array
     */
    protected $settings = array(
        "label" => "Static Job Demo",
        "description" => "Demonstration of a Static Job",
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
     */
    public function setDelay(int $delay): void
    {
        $this->setInputs(array("delay" => $delay));
    }

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    public function validate() : bool
    {
        $inputs = $this->getInputs();
        echo "Static Job => Validate Inputs </br>";
        if (is_integer($inputs["delay"])) {
            echo "Simple Job => Delay is a Integer </br>";
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare() : bool
    {
        echo "Static Job => Prepare for Action </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute() : bool
    {
        $inputs = $this->getInputs();
        echo "Static Job => Execute a ".$inputs["delay"]." Second Pause </br>";
        sleep($inputs["delay"]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize() : bool
    {
        echo "Static Job => Finalize Action </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close() : bool
    {
        echo "Static Job => Close Action </br>";

        return true;
    }
}
