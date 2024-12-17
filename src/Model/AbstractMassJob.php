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
use BadPixxel\Tasking\Interfaces\MassJobInterface;
use BadPixxel\Tasking\Model\Jobs\Advanced\ExtendedInputsTrait;
use BadPixxel\Tasking\Model\Jobs\Advanced\RepeatableTrait;

/**
 * Base Class for Background Mass Jobs Definition
 *
 * A Mass Job Execute an Action until Number of Pending Actions reach zéro.
 */
abstract class AbstractMassJob extends AbstractJob implements MassJobInterface
{
    use ExtendedInputsTrait;
    use RepeatableTrait;

    //==============================================================================
    //      Batch Job Execution Management
    //==============================================================================

    /**
     * Main function for Mass Jobs Management
     */
    public function execute(): bool
    {
        //==============================================================================
        // Request Latest Version of Jobs Count Estimation
        $jobsCount = $this->count();
        //==============================================================================
        // Check Mass Job Init
        if (!$this->isBooted()) {
            $this->setStateItem(JobState::COUNT, $jobsCount);
            $this->setBooted();
            if (empty($jobsCount)) {
                $this->setCompleted();

                return true;
            }
        }
        //====================================================================//
        // Init Task Planification Counters
        $maxTasks = min($jobsCount, $this->getPaginate());
        //====================================================================//
        // Mass Job Execution Loop
        for ($index = 0; $index < $maxTasks; $index++) {
            //==============================================================================
            // Update State
            $this->incStateItem(JobState::CURRENT);
            //==============================================================================
            // Execute User Batch Job
            $jobResult = $this->executeAction();
            //==============================================================================
            // Update Task State & Stop if Requested
            if (!$this->setJobResult($jobResult)) {
                return false;
            }
        }
        //==============================================================================
        // Manage End of Task by Count
        if ($maxTasks >= $jobsCount) {
            $this->setCompleted();
        }
        //==============================================================================
        //      Manage End of Task by Job Overloads
        if ($this->getStateItem(JobState::CURRENT) > (1.5 * (int) $this->getStateItem(JobState::COUNT))) {
            $this->setCompleted();
        }

        return true;
    }
}
