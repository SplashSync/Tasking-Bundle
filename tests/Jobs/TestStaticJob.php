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

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\AbstractStaticJob;

/**
 * Demonstration for Static Background Jobs
 */
class TestStaticJob extends AbstractStaticJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    protected static int $priority = Task::DO_LOWEST;

    /**
     * {@inheritdoc}
     */
    protected array $inputs = array("delay" => 1);

    /**
     * {@inheritdoc}
     */
    protected ?string $token = "JOB_STATIC";

    /**
     * {@inheritdoc}
     */
    protected int $frequency = 10;

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
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
