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
use Splash\Tasking\Model\AbstractMassJob;

/**
 * Demonstration for Simple Batch Jobs
 */
class TestMassJob extends AbstractMassJob
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
    protected ?string $token = "JOB_MASS";

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Test Mass Job",
        "description" => "Demonstration of a Mass Job",
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

    //==============================================================================
    //      Task Execution Management
    //==============================================================================

    /**
     * {@inheritdoc}
     */
    public function validate() : bool
    {
        $inputs = $this->getInputs();
        echo "Mass Job => Validate Inputs </br>";
        if (!is_integer($inputs["nbTasks"])) {
            return false;
        }
        echo "Mass Job => Nb Tasks is a Integer </br>";
        if (!is_integer($inputs["msDelay"])) {
            return false;
        }
        echo "Mass Job => Ms Delay is a Integer </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare() : bool
    {
        echo "Mass Job => Prepare for Action </br>";

        return true;
    }

    /**
     * Override this function to count number of remaining loops to perform
     *
     * @param array $inputs
     *
     * @return int
     */
    public function count(array $inputs = array()) : int
    {
        return (int) $inputs["nbTasks"] - (int) $this->getStateItem("jobsCompleted");
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $inputs = array()): bool
    {
        $msDelay = (int) (1E3 * $inputs["msDelay"]);
        echo "Mass Job => Execute a ".$inputs["msDelay"]." Microsecond Pause </br>";
        usleep($msDelay);
        $this->incStateItem("totalDelay", $msDelay);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize() : bool
    {
        echo "Mass Job => Finalize Action </br>";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close() : bool
    {
        echo "Mass Job => Close Action </br>";

        return true;
    }
}
