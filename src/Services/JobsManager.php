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

namespace Splash\Tasking\Services;

use Exception;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractStaticJob;

/**
 * Manage Available Jobs
 */
class JobsManager
{
    /**
     * @var AbstractJob[]
     */
    private array $jobs = array();

    /**
     * Command Constructor
     *
     * @throws Exception
     */
    public function __construct(
        iterable $taggedJobs
    ) {
        foreach ($taggedJobs as $key => $taggedJob) {
            $this->jobs[$key] = $this->validate($taggedJob);
        }
    }

    /**
     * Get All Available Jobs
     *
     * @return AbstractJob[]
     */
    public function getAll(): array
    {
        return $this->jobs;
    }

    /**
     * Get Job by Key
     *
     * @throws Exception
     */
    public function get(string $key): AbstractJob
    {
        //====================================================================//
        // Search by Service Keys/Name
        if (isset($this->jobs[$key])) {
            return $this->jobs[$key];
        }
        //====================================================================//
        // Search by Service Class
        foreach ($this->jobs as $job) {
            if (get_class($job) == $key) {
                return $job;
            }
        }

        throw new Exception(sprintf(
            "Job %s was not found, did you register it as service?",
            $key,
        ));
    }

    /**
     * Get All Static Jobs
     *
     * @return AbstractStaticJob[]
     */
    public function getStaticJobs(): array
    {
        /** @var null|AbstractStaticJob[] $staticJobs */
        static $staticJobs;

        if (!isset($staticJobs)) {
            $staticJobs = array();
            foreach ($this->getAll() as $key => $job) {
                if (!$job instanceof AbstractStaticJob) {
                    continue;
                }
                $staticJobs[$key] = $job;
            }
        }

        return $staticJobs;
    }

    /**
     * Check if a Job is a Static Jobs
     */
    public function isStaticJobs(string $key): bool
    {
        return in_array($key, array_keys($this->getStaticJobs()), true);
    }

    /**
     * Verify Job Configuration
     *
     * @throws Exception
     */
    private function validate(object $job): AbstractJob
    {
        //====================================================================//
        // Job Must Extend AbstractJob Class
        if (!$job instanceof AbstractJob) {
            throw new Exception(sprintf(
                "Job %s must extend %s",
                get_class($job),
                AbstractJob::class
            ));
        }

        return $job;
    }
}
