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

namespace Splash\Tasking\Model;

use Exception;
use Splash\Tasking\Entity\Task;
use Splash\Tasking\Model\Jobs\InputsStateTrait;

/**
 * Base Class for Background Mass Jobs Definition
 *
 * A Mass Job Execute an Action until Number of Pending Actions reach zéro.
 */
abstract class AbstractMassJob extends AbstractJob
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
    protected static $action = "run";

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
     * Class Constructor
     */
    public function __construct()
    {
        $this->setInputs(array());
        $this->setState(array());
        $this->setToken(get_class($this)."::".static::$action);
    }

    //==============================================================================
    //      Prototypes for User Mass Job
    //==============================================================================

    /**
     * Override this function to count number of remaining loops to perform
     *
     * @param array $inputs
     *
     * @throws Exception
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function count(array $inputs = array()) : int
    {
        throw new Exception(sprintf(
            "Class %s must implement %s method",
            static::class,
            __METHOD__
        ));
    }

    /**
     * Override this function to perform your task
     *
     * @param array $inputs
     *
     * @throws Exception
     *
     * @return bool
     */
    public function execute(array $inputs = array()) : bool
    {
        throw new Exception(sprintf(
            "Class %s must implement %s method",
            static::class,
            __METHOD__
        ));
    }

    //==============================================================================
    //      Batch Job Execution Management
    //==============================================================================

    /**
     * Main function for Mass Jobs Management
     *
     * @throws Exception
     *
     * @return bool
     */
    public function run(): bool
    {
        //==============================================================================
        //      Check Mass Job Init
        if ((false == $this->getStateItem("isListLoaded"))) {
            $jobsCount = $this->count($this->getInputs());
            $this->setStateItem("isListLoaded", true);
            $this->setStateItem("jobsCount", $jobsCount);
            if (empty($jobsCount)) {
                return $this->setCompleted(true);
            }
        }
        //====================================================================//
        // Increment Current Mass Job State
        $this->incStateItem("tasksCount");
        //====================================================================//
        // Mass Job Execution Loop
        for ($index = 0; $index < static::$paginate; $index++) {
            //==============================================================================
            //      Update State
            $this->incStateItem("currentJob");
            //==============================================================================
            //      Execute User Batch Job
            $jobsResult = $this->execute($this->getInputs());
            //==============================================================================
            //      Update State
            $this->incStateItem("jobsCompleted");
            $this->incStateItem(($jobsResult ? "jobsSuccess" : "jobsError"));
            //==============================================================================
            //      Manage Stop on Error
            if (!$jobsResult && static::$stopOnError) {
                return $this->setCompleted(false);
            }
            //==============================================================================
            //      Manage End of Task by Count
            if (empty($this->count($this->getInputs()))) {
                return $this->setCompleted(true);
            }
            //==============================================================================
            //      Manage End of Task by Job Overloads
            if ($this->getStateItem("currentJob") > (1.5 * $this->getStateItem("jobsCount"))) {
                return $this->setCompleted(true);
            }
        }

        return true;
    }

    /**
     * Mark Mass Task as Completed
     *
     * @param bool $result
     *
     * @return bool
     */
    protected function setCompleted(bool $result): bool
    {
        $this->setStateItem("isCompleted", true);

        return $result;
    }
}
