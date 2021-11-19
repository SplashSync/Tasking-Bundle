<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
use Splash\Tasking\Model\AbstractBatchJob;

/**
 * Demonstration for Simple Batch Jobs
 */
class TestBatchJob extends AbstractBatchJob
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
     * Job Token is Used for concurrency Management
     * You can set it directly by overriding this constant
     * or by writing an array of parameters to setJobToken()
     *
     * @var string
     */
    protected $token = "JOB_BATCH";

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
        "label" => "Test Batch Job",
        "description" => "Demonstration of a Batch Job",
        "translation_domain" => false,
        "translation_params" => array(),
    );

    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * Set Batch Job Options
     *
     * @param int $nbTasks
     * @param int $msDelay
     *
     * @return self
     */
    public function setup(int $nbTasks = 100, int $msDelay = 100): self
    {
        $this->setInputs(array(
            "nbTasks" => $nbTasks,
            "msDelay" => $msDelay,
        ));

        return $this;
    }

    /**
     * Override this function to generate list of your batch tasks inputs
     *
     * @return array
     */
    public function configure() : array
    {
        $inputs = $this->getInputs();
        $batchList = array();
        for ($i = 0; $i < ($inputs["nbTasks"] ?: 10); $i++) {
            $batchList[] = array(
                "name" => "Job ".$i,
                "msDelay" => $inputs["msDelay"] ?: 100,
            );
        }

        self::$state["totalDelay"] = 0;

        return $batchList;
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
        echo "Batch Job => Validate Inputs </br>";
        if (is_integer($inputs["msDelay"])) {
            echo "Batch Job => Ms Delay is a Integer </br>";
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare() : bool
    {
        echo "Batch Job => Prepare for Action </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $inputs = array()): bool
    {
        $msDelay = (int) (1E3 * $inputs["msDelay"]);
        echo "Batch Job => Execute a ".$inputs["msDelay"]." Microsecond Pause </br>";
        usleep($msDelay);
        $this->incStateItem("totalDelay", $msDelay);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize() : bool
    {
        echo "Batch Job => Finalize Action </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close() : bool
    {
        echo "Batch Job => Close Action </br>";

        return true;
    }
}
