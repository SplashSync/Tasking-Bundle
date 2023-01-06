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
     * {@inheritdoc}
     */
    protected static int $priority = Task::DO_LOWEST;

    /**
     * {@inheritdoc}
     */
    protected ?string $token = "JOB_BATCH";

    /**
     * Job Frequency => How often (in Seconds) shall this task be executed
     *
     * @var int
     */
    protected int $frequency = 10;

    /**
     * {@inheritdoc}
     */
    protected array $settings = array(
        "label" => "Test Batch Job",
        "description" => "Demonstration of a Batch Job",
        "translation_domain" => false,
        "translation_params" => array(),
    );

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
     * {@inheritdoc}
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
        if (!is_integer($inputs["nbTasks"])) {
            return false;
        }
        echo "Batch Job => Nb Tasks is a Integer </br>";
        if (!is_integer($inputs["msDelay"])) {
            return false;
        }
        echo "Batch Job => Ms Delay is a Integer </br>";

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

    //==============================================================================
    //      Job Setup
    //==============================================================================

    /**
     * Get Default State
     *
     * @return array
     */
    protected static function getDefaultState(): array
    {
        return array_merge(
            parent::getDefaultState(),
            array("totalDelay" => 0)
        );
    }
}
