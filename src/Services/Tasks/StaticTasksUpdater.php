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

use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Services\Configuration;
use BadPixxel\Tasking\Services\Jobs\JobsManager;
use BadPixxel\Tasking\Services\TasksManager;

class StaticTasksUpdater
{
    public function __construct(
        private readonly Configuration $configuration,
        private readonly JobsManager $jobsManager,
        private readonly TasksManager $tasksManager,
        private readonly TaskFactory $taskFactory,
    ) {
    }

    /**
     *  Initialize Static Task Buffer in Database
     *  => Tasks are Loaded from Parameters
     *  => Or by registering Event dispatcher
     *
     * @return $this
     */
    public function loadStaticTasks(): self
    {
        //====================================================================//
        // Get List of Static Tasks to Register
        $staticTasks = $this->getRegisteredTasks();
        //====================================================================//
        // Get List of Static Tasks in Database
        $dbTasks = $this->configuration->getTasksRepository()->getStaticTasks();
        //====================================================================//
        // Loop on All Database Tasks to Identify Static Tasks
        foreach ($dbTasks as $dbTask) {
            $found = false;
            //====================================================================//
            // Try to Identify Task in Static Task List
            foreach ($staticTasks as $index => $staticTask) {
                //====================================================================//
                // If Tasks Are Similar => Delete From List
                if ($this->compare($staticTask, $dbTask)) {
                    $found = true;
                    unset($staticTasks[$index]);
                }
            }
            //====================================================================//
            // Task Not to Run (Doesn't Exists) => Delete from Database
            if (!$found) {
                $this->configuration->getEntityManager()->remove($dbTask);
                $this->configuration->getEntityManager()->flush();
            }
        }

        //====================================================================//
        // Loop on Tasks to Add it On Database
        foreach ($staticTasks as $staticTask) {
            //====================================================================//
            // Persist New Task to Db
            $this->tasksManager->start($staticTask->getJobClass(), array());
        }

        return $this;
    }

    /**
     * Get Static Jobs Configurations
     *
     * @return Task[]
     */
    private function getRegisteredTasks(): array
    {
        $staticTasks = array();
        //====================================================================//
        // Walk on Registered Static Tasks Services
        foreach (array_keys($this->jobsManager->getStaticJobs()) as $serviceId) {
            //====================================================================//
            // Build Static Task
            $staticTasks[$serviceId] = $this->taskFactory->fromConfiguration($serviceId, array());
        }

        return array_filter($staticTasks);
    }

    /**
     * Compare Configuration of Two Static Task
     *
     * @return bool true if Static Tasks are Similar
     */
    private function compare(Task $staticTask, Task $dbTask) : bool
    {
        //====================================================================//
        // Filter by Class Name
        if ($staticTask->getJobClass() != $dbTask->getJobClass()) {
            return false;
        }
        //====================================================================//
        // Filter by Token
        if ($staticTask->getJobToken() != $dbTask->getJobToken()) {
            return false;
        }
        //====================================================================//
        // Filter by Frequency
        if ($staticTask->getJobFrequency() != $dbTask->getJobFrequency()) {
            return false;
        }
        //====================================================================//
        // Filter by Inputs
        if ($staticTask->getDiscriminator() !== $dbTask->getDiscriminator()) {
            return false;
        }

        return true;
    }
}
