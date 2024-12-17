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

namespace BadPixxel\Tasking\Dictionary;

enum RepeatableJobState
{
    /**
     * State Key For Job Completed Flag
     */
    const COMPLETED = "isCompleted";

    /**
     * State Key For Job Initiated Flag
     */
    const BOOTED = "isBooted";

    /**
     * Total Number of Jobs
     */
    const COUNT = "jobCount";

    /**
     * Number of Executed Jobs
     */
    const EXECUTED = "jobsCompleted";

    /**
     * Number of Succeeded Jobs
     */
    const SUCCESS = "jobsSuccess";

    /**
     * Number of Errored Jobs
     */
    const ERROR = "jobsError";

    /**
     * Current Job Index
     */
    const CURRENT = "current";
}
