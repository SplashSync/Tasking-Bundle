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

namespace Splash\Tasking\Model;

use Splash\Tasking\Entity\Task;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base Class for Background Batch Jobs Definition
 */
abstract class AbstractBatchJob extends AbstractJob
{
    //==============================================================================
    //  Constants Definition
    //==============================================================================

    /**
     * Job Action Method Name
     *
     * @var string
     */
    protected static $action = "batch";

    /**
     * Batch Job Inputs List Method Name
     *
     * @var string
     */
    protected static $batchList = "configure";

    /**
     * Batch Job Action Method Name
     *
     * @var string
     */
    protected static $batchAction = "execute";

    /**
     * Parameter - Stop on Errors
     * => If Set, if one of the batch action return False, batch action is stopped
     *
     * @var bool
     */
    protected static $stopOnError = true;

    /**
     * Job Priority
     *
     * @var int
     */
    protected static $priority = Task::DO_LOWEST;

    /**
     * Parameter - Batch Action Pagination.
     * => Number of tasks to start on each batch step
     *
     * @var int
     */
    protected static $paginate = 1;

    /**
     * Define Initial State for a Batch Action
     *
     * @var array
     */
    protected static $state = array(
        //==============================================================================
        //  General Status Flags
        'isCompleted' => false,
        'isListLoaded' => false,

        //==============================================================================
        //  Batch Counters
        'tasksCount' => 0,
        'jobsCount' => 0,
        'jobsCompleted' => 0,
        'jobsSuccess' => 0,
        'jobsError' => 0,

        //==============================================================================
        //  Batch Execution
        "currentJob" => 0,
    );

    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setInputs(array());
        $this->setState(array());
        $this->setToken(get_class($this)."::".static::$action);
    }

    //==============================================================================
    //      Prototypes for User Batch Job
    //==============================================================================

    /**
     * Overide this function to generate list of your batch tasks inputs
     *
     * @return array
     */
    public function configure() : array
    {
        $batchList = array();
        for ($i = 1; $i < 3; $i++) {
            $batchList[] = array(
                "name" => "Job ".$i,
                "delay" => $i ,
            );
        }

        return $batchList;
    }

    /**
     * Overide this function to perform your task
     */
    public function execute(array $inputs = array()) : bool
    {
        echo "<h4>Default Batch Action : ".$inputs["name"]."</h4>";
        echo " => Delay of : ".$inputs["delay"]." Seconds</br>";
        echo "Overide Execute function to define your own Batch Action </br>";
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
        if ((false == $this->getStateItem("isListLoaded")) && !$this->batchLoadJobsList()) {
            return false;
        }
        //==============================================================================
        //      Safety Ckeck - Ensure Execute Method Exists
        if (!method_exists($this, static::$batchAction)) {
            $this->setStateItem("isCompleted", true);

            return true;
        }
        //====================================================================//
        // Load Current Batch State
        $state = $this->getState();
        $state["tasksCount"]++;

        //==============================================================================
        //      Execute Batch Tasks
        //==============================================================================

        //====================================================================//
        // Init Task Planification Counters
        $taskStart = $state["currentJob"];
        $taskMax = $state["jobsCount"];
        $taskEnd = (static::$paginate > 0) ? ($taskStart + static::$paginate) : $taskMax;
        if ($taskEnd > $taskMax) {
            $taskEnd = $taskMax;
        }

        //====================================================================//
        // Batch Execution Loop
        for ($index = $taskStart; $index < $taskEnd; $index++) {
            //==============================================================================
            //      Update State
            $state["currentJob"]++;

            //==============================================================================
            //      Safety Ckeck - Ensure Input Array Exists
            if (is_null($jobInputs = $this->getJobInputs($index))) {
                $this->setStateItem("isCompleted", true);

                return false;
            }

            //==============================================================================
            //      Execute User Batch Job
            $jobsResult = $this->{ static::$batchAction }($jobInputs);

            //==============================================================================
            //      Update State
            $state["jobsCompleted"]++;
            $jobsResult ? $state["jobsSuccess"]++ : $state["jobsError"]++;
            $this->setState($state);

            //==============================================================================
            //      Manage Stop on Error
            if (!$jobsResult && static::$stopOnError) {
                $this->setStateItem("isCompleted", true);

                return false;
            }
        }

        //==============================================================================
        //      Manage Stop on Error
        if ($state["currentJob"] >= $state["jobsCount"]) {
            $this->setStateItem("isCompleted", true);
        }

        return true;
    }

    /**
     * Load Jobs Batch Actions Inputs fro User function
     *
     * @return bool
     */
    public function batchLoadJobsList() : bool
    {
        //==============================================================================
        //      Safety Ckeck - Ensure Configure Method Exists
        if (!method_exists($this, static::$batchList)) {
            return false;
        }

        //==============================================================================
        //      Read List of Jobs from User Function
        $jobsInputs = $this->{ static::$batchList }($this->getInputs());

        //==============================================================================
        //      Check List is not Empty
        if (count($jobsInputs) > 0) {
            $this->setStateItem("isCompleted", true);

            return true;
        }

        //==============================================================================
        //      Setup List
        $this->setJobsList($jobsInputs);

        //==============================================================================
        //      Init Batch State
        $state = static::$state;
        $state["isListLoaded"] = true;
        $state["jobsCount"] = count($jobsInputs);
        $this->setState($state);

        return true;
    }

    /**
     * Check if batch actions are completed or task needs to be executed again (pagination)
     *
     * @return bool
     */
    public function isCompleted() : bool
    {
        return $this->inputs["state"]["isCompleted"];
    }

    /**
     * Check if Errors have occured during batch action
     *
     * @return bool
     */
    public function hasErrors() : bool
    {
        return $this->inputs["state"]["jobsError"] ? true : false;
    }

    //==============================================================================
    //      Specific Getters & Setters
    //==============================================================================

    /**
     * Set Job User Inputs
     *
     * @param array $inputs
     *
     * @return $this
     */
    public function setInputs(array $inputs): AbstractJob
    {
        $this->inputs["inputs"] = $inputs;

        return $this;
    }

    /**
     * Get Job User Inputs
     *
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs["inputs"];
    }

    /**
     * Set Job Status
     *
     * @param array $state
     *
     * @return $this
     */
    public function setState(array $state): self
    {
        //==============================================================================
        //  Init State Array using OptionResolver
        $resolver = (new OptionsResolver())->setDefaults(static::$state);
        //==============================================================================
        //  Update State Array using OptionResolver
        try {
            $this->inputs["state"] = $resolver->resolve($state);
            //==============================================================================
        //  Invalid Field Definition Array
        } catch (UndefinedOptionsException $ex) {
            $this->inputs["state"] = static::$state;
        } catch (InvalidOptionsException $ex) {
            $this->inputs["state"] = static::$state;
        }

        return $this;
    }

    /**
     * Get Job Status
     *
     * @return array
     */
    public function getState(): array
    {
        return $this->inputs["state"];
    }

    /**
     * Set Batch Action State Item
     *
     * @param string $index
     * @param mixed  $value
     *
     * @return self
     */
    public function setStateItem(string $index, $value): self
    {
        //==============================================================================
        // Read Full State Array
        $state = $this->getState();
        //==============================================================================
        // Update Item
        $state[$index] = $value;
        //==============================================================================
        // Update Full State Array
        $this->setState($state);

        return $this;
    }

    /**
     * Get Batch Action State Item
     *
     * @param string $index
     *
     * @return mixed
     */
    public function getStateItem(string $index)
    {
        if (isset($this->inputs["state"][$index])) {
            return $this->inputs["state"][$index];
        }

        return null;
    }

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
