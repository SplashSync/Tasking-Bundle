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

namespace BadPixxel\Tasking\Model\Configuration;

/**
 * Access to Tasking Bundle Parameters
 */
trait TasksParametersGettersTrait
{
    /**
     * Get Tasks Option for Searching Next Task
     */
    public function getTasksSearchOptions(): array
    {
        return array_merge($this->config['tasks'], $this->config['token']);
    }

    /**
     * Get Max Age for TAks before being Deleted
     */
    public function getTasksDeleteDelay(): int
    {
        return (int) $this->config['tasks']['max_age'];
    }

    /**
     * Get Number of Times to Retry a Task
     */
    public function getTasksMaxRetry(): int
    {
        return (int) $this->config['tasks']['try_count'];
    }

    /**
     * Get Delay before retrying a Task
     */
    public function getTasksRetryDelay(): int
    {
        return (int) $this->config['tasks']['try_delay'];
    }

    /**
     * Get Task Error Delay
     */
    public function getTasksErrorDelay(): int
    {
        return (int) $this->config['tasks']['error_delay'];
    }

    /**
     * Complete Configuration for Tasks
     */
    protected static function completeTasksConfiguration(array &$configuration): void
    {
        // Compute Tasks Error Delay
        $configuration["tasks"]["error_delay"] = 8 * $configuration["watchdog_delay"];
    }
}
