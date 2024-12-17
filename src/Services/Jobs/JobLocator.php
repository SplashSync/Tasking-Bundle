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

namespace BadPixxel\Tasking\Services\Jobs;

use BadPixxel\Tasking\Dictionary\TaskingTags;
use BadPixxel\Tasking\Interfaces\BatchJobInterface;
use BadPixxel\Tasking\Interfaces\JobInterface;
use BadPixxel\Tasking\Interfaces\MassJobInterface;
use BadPixxel\Tasking\Interfaces\RepeatableJobInterface;
use BadPixxel\Tasking\Interfaces\StaticJobInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Manage Access to Available Jobs Services
 */
class JobLocator
{
    /**
     * Service Constructor
     *
     * @param JobInterface[] $jobServices
     */
    public function __construct(
        #[TaggedIterator(TaskingTags::JOB, indexAttribute: "key")]
        private readonly iterable $jobServices,
    ) {
    }

    //====================================================================//
    // ALL JOBS
    //====================================================================//

    /**
     * Get All Available Jobs Services
     *
     * @return JobInterface[]
     */
    public function getAll(): iterable
    {
        return $this->jobServices;
    }

    /**
     * Get Job Service by ID or Class
     */
    public function get(string $serviceIdOrClass): ?JobInterface
    {
        foreach ($this->jobServices as $serviceId => $jobService) {
            //====================================================================//
            // Search by Service ID
            if ($serviceId == $serviceIdOrClass) {
                return $jobService;
            }
            //====================================================================//
            // Search by Service Class
            if (get_class($jobService) == $serviceIdOrClass) {
                return $jobService;
            }
        }

        return null;
    }

    //====================================================================//
    // STATIC JOBS
    //====================================================================//

    /**
     * Get All Static Jobs Services
     *
     * @return StaticJobInterface[]
     */
    public function getStaticJobs(): array
    {
        static $staticJobs;
        //====================================================================//
        // Static Job Already Loaded
        if (isset($staticJobs)) {
            return $staticJobs;
        }
        //====================================================================//
        // Build List of Static Job
        $staticJobs = array();
        foreach ($this->getAll() as $serviceId => $jobService) {
            $staticJobs[$serviceId] = $this->isStatic($jobService);
        }

        return $staticJobs = array_filter($staticJobs);
    }

    /**
     * Check if a Job is a Static Job
     */
    public function isStatic(JobInterface $jobService): ?StaticJobInterface
    {
        return ($jobService instanceof StaticJobInterface) ? $jobService : null;
    }

    //====================================================================//
    // REPEATABLE JOBS
    //====================================================================//

    /**
     * Check if a Job is a Repeatable Job
     */
    public function isRepeatable(JobInterface $jobService): ?RepeatableJobInterface
    {
        return ($jobService instanceof RepeatableJobInterface) ? $jobService : null;
    }

    //====================================================================//
    // BATCH JOBS
    //====================================================================//

    /**
     * Get All Batch Jobs
     *
     * @return BatchJobInterface[]
     */
    public function getBatchJobs(): array
    {
        static $batchJobs;
        //====================================================================//
        // Batch Jobs Already Loaded
        if (isset($batchJobs)) {
            return $batchJobs;
        }
        //====================================================================//
        // Build List of Batch Jobs
        $batchJobs = array();
        foreach ($this->getAll() as $serviceId => $jobService) {
            $batchJobs[$serviceId] = $this->isBatch($jobService);
        }

        return $batchJobs = array_filter($batchJobs);
    }

    /**
     * Check if a Job is a Batch Job
     */
    public function isBatch(JobInterface $jobService): ?BatchJobInterface
    {
        return ($jobService instanceof BatchJobInterface) ? $jobService : null;
    }

    //====================================================================//
    // MASS JOBS
    //====================================================================//

    /**
     * Get All Mass Jobs
     *
     * @return MassJobInterface[]
     */
    public function getMassJobs(): array
    {
        static $massJobs;
        //====================================================================//
        // Mass Jobs Already Loaded
        if (isset($massJobs)) {
            return $massJobs;
        }
        //====================================================================//
        // Build List of Mass Jobs
        $massJobs = array();
        foreach ($this->getAll() as $serviceId => $jobService) {
            $massJobs[$serviceId] = $this->isMass($jobService);
        }

        return $massJobs = array_filter($massJobs);
    }

    /**
     * Check if a Job is a Mass Job
     */
    public function isMass(JobInterface $jobService): ?MassJobInterface
    {
        return ($jobService instanceof MassJobInterface) ? $jobService : null;
    }
}
