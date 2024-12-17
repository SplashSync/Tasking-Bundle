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

namespace BadPixxel\Tasking\Model;

use BadPixxel\Tasking\Dictionary\RepeatableJobState as JobState;
use BadPixxel\Tasking\Interfaces\BatchJobInterface;
use BadPixxel\Tasking\Model\Jobs\Advanced\ExtendedInputsTrait;
use BadPixxel\Tasking\Model\Jobs\Advanced\RepeatableTrait;

/**
 * Base Class for Background Batch Jobs Definition
 *
 * A Batch Job defines a list of Jobs to be Executed
 * At the beginning, a list of Jobs is Build
 * Then each job is executed subsequently
 */
abstract class AbstractBatchJob extends AbstractJob implements BatchJobInterface
{
    use ExtendedInputsTrait;
    use RepeatableTrait;

    //==============================================================================
    //      Batch Job Execution Management
    //==============================================================================

    /**
     * Main function for Batch Jobs Management
     */
    public function execute(): bool
    {
        //==============================================================================
        // Check Batch Job List is Loaded (Or Try to Load It)
        if (!$this->isBooted() && !$this->batchLoadJobsList()) {
            return false;
        }

        //==============================================================================
        //      Execute Batch Tasks
        //==============================================================================

        //====================================================================//
        // Init Task Planification Counters
        $taskStart = (int) $this->getStateItem(JobState::CURRENT);
        $taskEnd = min(
            $this->getStateItem(JobState::COUNT),
            $taskStart + $this->getPaginate()
        );

        //====================================================================//
        // Batch Execution Loop
        for ($index = $taskStart; $index < $taskEnd; $index++) {
            //==============================================================================
            // Update State
            $this->incStateItem(JobState::CURRENT);
            //==============================================================================
            // Safety Check - Ensure Input Array Exists
            $jobInputs = $this->getJobInputs((string) $index);
            if (!is_array($jobInputs)) {
                $this->setCompleted();

                return false;
            }
            //==============================================================================
            // Execute User Batch Job
            $jobResult = $this->executeAction($jobInputs);
            //==============================================================================
            // Update Task State & Stop if Requested
            if (!$this->setJobResult($jobResult)) {
                return false;
            }
        }

        //==============================================================================
        // Manage End of Task by Job Overloads
        if ($this->getStateItem(JobState::CURRENT) >= $this->getStateItem(JobState::COUNT)) {
            $this->setCompleted();
        }

        return true;
    }

    /**
     * Get Job Inputs
     *
     * @param string $index
     *
     * @return mixed
     */
    public function getJobInputs(string $index): mixed
    {
        if (isset($this->inputs["jobs"][$index])) {
            return $this->inputs["jobs"][$index];
        }

        return null;
    }

    /**
     * Load Jobs Batch Actions Inputs for User function
     */
    private function batchLoadJobsList() : bool
    {
        //==============================================================================
        // Read List of Jobs from Batch Job Service
        $jobsInputs = $this->configure();
        //==============================================================================
        //      Check List is not Empty
        if (empty($jobsInputs)) {
            $this->setStateItem(JobState::COMPLETED, true);

            return true;
        }
        //==============================================================================
        // Setup List
        $this->inputs["jobs"] = array_values($jobsInputs);
        //==============================================================================
        // Init Batch State
        $this->setStateItem(JobState::BOOTED, true);
        $this->setStateItem(JobState::COUNT, count($jobsInputs));

        return true;
    }
}
