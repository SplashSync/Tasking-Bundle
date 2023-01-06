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
use Splash\Tasking\Model\Jobs\InputsStateTrait;

/**
 * Base Class for Background Batch Jobs Definition
 *
 * A Batch Job defines a list of Jobs to be Executed
 * At the beginning, a list of Jobs is Build
 * Then each job is executed subsequently
 */
abstract class AbstractBatchJob extends AbstractJob
{
    use InputsStateTrait;

    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Action Method Name
     *
     * @var string
     */
    protected static string $action = "batch";

    /**
     * Batch Job Inputs List Method Name
     *
     * @var string
     */
    protected static string $batchList = "configure";

    /**
     * Batch Job Action Method Name
     *
     * @var string
     */
    protected static string $batchAction = "execute";

    /**
     * Parameter - Stop on Errors
     * => If Set, if one of the batch action return False, batch action is stopped
     *
     * @var bool
     */
    protected static bool $stopOnError = true;

    /**
     * Job Priority
     *
     * @var int
     */
    protected static int $priority = Task::DO_LOWEST;

    /**
     * Parameter - Batch Action Pagination.
     * => Number of tasks to start on each batch step
     *
     * @var int
     */
    protected static int $paginate = 1;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        $this->setInputs(array());
        $this->setState(array());
        $this->setToken(get_class($this)."::".static::$action);
    }

    //==============================================================================
    //      Prototypes for User Batch Job
    //==============================================================================

    /**
     * Override this function to generate list of your batch tasks inputs
     *
     * @return array
     */
    public function configure() : array
    {
        $batchList = array();
        for ($i = 1; $i <= 3; $i++) {
            $batchList[] = array(
                "name" => "Job ".$i,
                "delay" => 1,
            );
        }

        return $batchList;
    }

    /**
     * Override this function to perform your task
     *
     * @param array $inputs
     *
     * @return bool
     */
    public function execute(array $inputs = array()) : bool
    {
        echo "<h4>Default Batch Action : ".$inputs["name"]."</h4>";
        echo " => Delay of : ".$inputs["delay"]." Seconds</br>";
        echo "Override Execute function to define your own Batch Action </br>";
        sleep($inputs["delay"]);

        return true;
    }

    /**
     * Verify Batch Job Actions Methods are Available
     *
     * @return bool
     */
    public static function validateBatchJobActions(): bool
    {
        //====================================================================//
        // If Batch Actions Names are Empty
        if ((null == static::$batchList) || (null == static::$batchAction)) {
            return false;
        }
        if (!method_exists(self::class, static::$batchList) || !method_exists(self::class, static::$batchAction)) {
            return false;
        }

        return true;
    }

    //==============================================================================
    //      Batch Job Execution Management
    //==============================================================================

    /**
     * Main function for Batch Jobs Management
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function batch(): bool
    {
        //==============================================================================
        //      Check Batch Job List is Loaded (Or Try to Load It)
        if (!$this->getStateItem("isListLoaded") && !$this->batchLoadJobsList()) {
            return false;
        }
        //==============================================================================
        //      Safety Check - Ensure Execute Method Exists
        if (!method_exists($this, static::$batchAction)) {
            $this->setStateItem("isCompleted", true);

            return true;
        }
        //====================================================================//
        // Increment Current Batch State
        $this->incStateItem("tasksCount");

        //==============================================================================
        //      Execute Batch Tasks
        //==============================================================================

        //====================================================================//
        // Init Task Planification Counters
        $taskStart = (int) $this->getStateItem("currentJob");
        $taskMax = $this->getStateItem("jobsCount");
        $taskEnd = (static::$paginate > 0) ? ($taskStart + static::$paginate) : $taskMax;
        if ($taskEnd > $taskMax) {
            $taskEnd = $taskMax;
        }

        //====================================================================//
        // Batch Execution Loop
        for ($index = $taskStart; $index < $taskEnd; $index++) {
            //==============================================================================
            //      Update State
            $this->incStateItem("currentJob");

            //==============================================================================
            //      Safety Check - Ensure Input Array Exists
            $jobInputs = $this->getJobInputs((string) $index);
            if (is_null($jobInputs)) {
                $this->setStateItem("isCompleted", true);

                return false;
            }

            //==============================================================================
            //      Execute User Batch Job
            $jobsResult = $this->{ static::$batchAction }($jobInputs);

            //==============================================================================
            //      Update State
            $this->incStateItem("jobsCompleted");
            $this->incStateItem(($jobsResult ? "jobsSuccess" : "jobsError"));

            //==============================================================================
            //      Manage Stop on Error
            if (!$jobsResult && static::$stopOnError) {
                $this->setStateItem("isCompleted", true);

                return false;
            }
        }

        //==============================================================================
        //      Manage Stop on Error
        if ($this->getStateItem("currentJob") >= $this->getStateItem("jobsCount")) {
            $this->setStateItem("isCompleted", true);
        }

        return true;
    }

    /**
     * Load Jobs Batch Actions Inputs for User function
     *
     * @return bool
     */
    public function batchLoadJobsList() : bool
    {
        //==============================================================================
        //      Safety Check - Ensure Configure Method Exists
        if (!method_exists($this, static::$batchList)) {
            return false;
        }

        //==============================================================================
        //      Read List of Jobs from User Function
        $jobsInputs = $this->{ static::$batchList }($this->getInputs());

        //==============================================================================
        //      Check List is not Empty
        if (!is_array($jobsInputs) || (0 == count($jobsInputs))) {
            $this->setStateItem("isCompleted", true);

            return true;
        }

        //==============================================================================
        //      Setup List
        $this->setJobsList($jobsInputs);

        //==============================================================================
        //      Init Batch State
        $this->setStateItem("isListLoaded", true);
        $this->setStateItem("jobsCount", count($jobsInputs));

        return true;
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Set Jobs List
     *
     * @param array $list
     *
     * @return $this
     */
    public function setJobsList(array $list): self
    {
        //==============================================================================
        // Parse Jobs Inputs List to a Numeric Array
        $this->inputs["jobs"] = array();
        foreach ($list as $job) {
            $this->inputs["jobs"][] = $job;
        }

        return $this;
    }

    /**
     * Get Jobs List
     *
     * @return array
     */
    public function getJobList(): array
    {
        return $this->inputs["jobs"];
    }

    /**
     * Get Job Inputs
     *
     * @param string $index
     *
     * @return mixed
     */
    public function getJobInputs(string $index)
    {
        if (isset($this->inputs["jobs"][$index])) {
            return $this->inputs["jobs"][$index];
        }

        return null;
    }
}
