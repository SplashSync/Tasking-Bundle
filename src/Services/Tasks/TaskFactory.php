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

namespace BadPixxel\Tasking\Services\Tasks;

use BadPixxel\Tasking\Dictionary\JobOptions;
use BadPixxel\Tasking\Dictionary\RepeatableJobOptions;
use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Interfaces\JobInterface;
use BadPixxel\Tasking\Interfaces\RepeatableJobInterface;
use BadPixxel\Tasking\Services\Jobs\JobsManager;

/**
 * Task Factory: Create Tasks from Received Configuration
 */
class TaskFactory
{
    public function __construct(
        private readonly JobsManager $jobsManager,
    ) {
    }

    /**
     * Build a Task from Received Configuration
     */
    public function fromConfiguration(string $serviceIdOrClass, array $options): ?Task
    {
        //====================================================================//
        // Connect to Job Service
        $jobService = $this->jobsManager->getService($serviceIdOrClass);
        if (!$jobService) {
            return null;
        }
        //====================================================================//
        // Prepare Job Configuration
        $jobConfiguration = $this->jobsManager->getConfiguration($serviceIdOrClass, $options);
        if (!$jobConfiguration) {
            return null;
        }
        //====================================================================//
        // Create a New Task
        $task = new Task();
        //====================================================================//
        // Configure Task
        $this
            ->configure($task, $serviceIdOrClass, $jobConfiguration)
            ->configureStaticTasks($task, $jobService, $jobConfiguration)
            ->configureRepeatableTasks($task, $jobService, $jobConfiguration)
            ->configureBatchTasks($task, $jobService)
            ->configureMassTasks($task, $jobService)
        ;
        //====================================================================//
        // Update Task Discriminator
        $task->updateDiscriminator();

        return $task;
    }

    /**
     * Configure Task General Parameters for Storage
     */
    private function configure(Task $task, string $serviceIdOrClass, array $jobConfiguration): static
    {
        //====================================================================//
        // Setup Task Parameters
        $task
            ->setName($serviceIdOrClass."->".$jobConfiguration[JobOptions::ACTION])
            ->setJobClass($serviceIdOrClass)
            ->setJobAction($jobConfiguration[JobOptions::ACTION])
            ->setJobInputs($jobConfiguration[JobOptions::INPUTS])
            ->setJobPriority($jobConfiguration[JobOptions::PRIORITY])
            ->setJobToken($jobConfiguration[JobOptions::TOKEN])
            ->setSettings($jobConfiguration[JobOptions::SETTINGS])
            ->setJobIndexKey1($jobConfiguration[JobOptions::INDEX_KEY_1])
            ->setJobIndexKey2($jobConfiguration[JobOptions::INDEX_KEY_2]);

        return $this;
    }

    /**
     * Configure Static Task Parameters for Storage
     */
    private function configureStaticTasks(Task $task, JobInterface $jobService, array $jobConfiguration): static
    {
        if (!$this->jobsManager->isStatic($jobService)) {
            return $this;
        }

        $task
            ->setName("[S] ".$task->getName())
            ->setJobIsStatic(true)
            ->setJobFrequency($jobConfiguration[JobOptions::FREQUENCY])
        ;

        return $this;
    }

    /**
     * Configure Repeatable Task Parameters for Storage
     */
    private function configureRepeatableTasks(Task $task, JobInterface $jobService, array $jobConfiguration): static
    {
        if (!$jobService instanceof RepeatableJobInterface) {
            return $this;
        }

        $task
            ->setJobInputs(array_replace_recursive(
                $jobConfiguration[JobOptions::INPUTS],
                array(
                    RepeatableJobOptions::PAGINATION => $jobConfiguration[RepeatableJobOptions::PAGINATION],
                    RepeatableJobOptions::STOP_ON_ERROR => $jobConfiguration[RepeatableJobOptions::STOP_ON_ERROR],
                )
            ))
        ;

        return $this;
    }

    /**
     * Configure Batch Task Parameters for Storage
     */
    private function configureBatchTasks(Task $task, JobInterface $jobService): static
    {
        if (!$this->jobsManager->isBatch($jobService)) {
            return $this;
        }

        $task
            ->setName("[B] ".$task->getName())
        ;

        return $this;
    }

    /**
     * Configure Mass Task Parameters for Storage
     */
    private function configureMassTasks(Task $task, JobInterface $jobService): static
    {
        if (!$this->jobsManager->isMass($jobService)) {
            return $this;
        }

        $task
            ->setName("[M] ".$task->getName())
        ;

        return $this;
    }
}
