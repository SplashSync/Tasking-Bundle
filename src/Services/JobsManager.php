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
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractMassJob;
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

    //====================================================================//
    // ALL JOBS
    //====================================================================//

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

    //====================================================================//
    // STATIC JOBS
    //====================================================================//

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
     * Check if a Job is a Static Job
     */
    public function isStaticJobs(string $key): ?AbstractStaticJob
    {
        return $this->getStaticJobs()[$key] ?? null;
    }

    //====================================================================//
    // BATCH JOBS
    //====================================================================//

    /**
     * Get All Batch Jobs
     *
     * @return AbstractBatchJob[]
     */
    public function getBatchJobs(): array
    {
        /** @var null|AbstractBatchJob[] $batchJobs */
        static $batchJobs;

        if (!isset($batchJobs)) {
            $batchJobs = array();
            foreach ($this->getAll() as $key => $job) {
                if (!$job instanceof AbstractBatchJob) {
                    continue;
                }
                $batchJobs[$key] = $job;
            }
        }

        return $batchJobs;
    }

    /**
     * Check if a Job is a Batch Job
     */
    public function isBatchJobs(string $key): ?AbstractBatchJob
    {
        return $this->getBatchJobs()[$key] ?? null;
    }

    //====================================================================//
    // MASS JOBS
    //====================================================================//

    /**
     * Get All Mass Jobs
     *
     * @return AbstractMassJob[]
     */
    public function getMassJobs(): array
    {
        /** @var null|AbstractMassJob[] $massJobs */
        static $massJobs;

        if (!isset($massJobs)) {
            $massJobs = array();
            foreach ($this->getAll() as $key => $job) {
                if (!$job instanceof AbstractMassJob) {
                    continue;
                }
                $massJobs[$key] = $job;
            }
        }

        return $massJobs;
    }

    /**
     * Check if a Job is a Mass Job
     */
    public function isMassJobs(string $key): ?AbstractMassJob
    {
        return $this->getMassJobs()[$key] ?? null;
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
