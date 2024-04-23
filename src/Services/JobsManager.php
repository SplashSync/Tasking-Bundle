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
use Splash\Tasking\Events\StaticTasksListingEvent;
use Splash\Tasking\Model\AbstractBatchJob;
use Splash\Tasking\Model\AbstractJob;
use Splash\Tasking\Model\AbstractMassJob;
use Splash\Tasking\Model\StaticJobInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        #[TaggedIterator(AbstractJob::TAG, indexAttribute: "key")]
        iterable $taggedJobs,
        private EventDispatcherInterface $dispatcher,
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
     * @throws Exception
     *
     * @return AbstractJob[]
     */
    public function getStaticJobs(): array
    {
        /** @var null|AbstractJob[] $staticJobs */
        static $staticJobs;
        //====================================================================//
        // Static Job Already Loaded
        if (isset($staticJobs)) {
            return $staticJobs;
        }
        //====================================================================//
        // Build List of Static Job
        $staticJobs = array();
        //====================================================================//
        // Directly Injected of Static Job
        foreach ($this->getAll() as $key => $job) {
            if (($job instanceof StaticJobInterface) && ($job->getFrequency() > 0)) {
                $staticJobs[$key] = $job;
            }
        }
        //====================================================================//
        // Collect Static Job from Event
        $staticJobs = array_merge($staticJobs, $this->getStaticJobsFromListeners());

        return $staticJobs;
    }

    /**
     * Get All Static Jobs
     *
     * @throws Exception
     *
     * @return AbstractJob[]
     */
    public function getStaticJobsFromListeners(): array
    {
        //====================================================================//
        // Collect Static Job from Event
        $event = new StaticTasksListingEvent();
        $this->dispatcher->dispatch($event);
        //====================================================================//
        // Walk on Collected Static Jobs
        $staticJobs = array();
        foreach ($event->getArguments() as $jobKey => $jobArgs) {
            //====================================================================//
            // Detect Target Job
            $targetJob = clone $this->get($jobArgs['class'] ?? "Undefined");
            //====================================================================//
            // Configure Target Job
            $targetJob
                ->setFrequency($jobArgs['frequency'] ?? 60)
                ->setToken($jobArgs['token'] ?? $jobKey)
                ->setInputs($jobArgs['inputs'] ?? array())
            ;
            //====================================================================//
            // Add Job to Queue
            $staticJobs[$jobKey] = $targetJob;
        }

        return $staticJobs;
    }

    /**
     * Check if a Job is a Static Job
     */
    public function isStaticJobs(string $key): ?AbstractJob
    {
        try {
            return $this->getStaticJobs()[$key] ?? null;
        } catch (Exception $e) {
            return null;
        }
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
