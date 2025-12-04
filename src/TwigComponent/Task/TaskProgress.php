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

namespace BadPixxel\Tasking\TwigComponent\Task;

use BadPixxel\Tasking\Dictionary\RepeatableJobState;
use BadPixxel\Tasking\Entity\Task;
use BadPixxel\Tasking\Model\Component\ComponentWithRefreshTrait;
use BadPixxel\Tasking\Services\Configuration;
use BadPixxel\Tasking\Services\Jobs\JobsManager;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Webmozart\Assert\Assert;

/**
 * Render Global Status of Tasking System
 */
#[AsTwigComponent(
    name:       "Tasking:Task:Progress",
    template:   "@BadpixxelTasking/Component/Task/progress.html.twig"
)]
class TaskProgress
{
    use DefaultActionTrait;
    use ComponentWithRefreshTrait;

    /**
     * Task Progress Status Summary
     *
     * @var array<string, array|scalar>
     *
     * @phpstan-var array{
     *     'id': string,
     *     'jobClass': string,
     *     'pending': int,
     *     'running': int,
     *     'waiting': int,
     *     'finished': int,
     *     'total': int,
     *     'settings': array
     * }
     */
    public array $status;

    /**
     * Current Task if Batch or Mass Job
     */
    private ?Task $task = null;

    public function __construct(
        private readonly JobsManager $jobsManager,
        private readonly Configuration $configuration,
    ) {
    }

    #[PostMount]
    public function identifyAdvancedTask(): void
    {
        //====================================================================//
        // More than One Tasks Waiting => Not a Complex task
        if (1 !== $this->status["pending"]) {
            return;
        }
        Assert::stringNotEmpty($this->status["jobClass"]);
        //====================================================================//
        // Check if Batch Task
        if ($this->jobsManager->isRepeatable($this->status["jobClass"])) {
            Assert::notEmpty($this->status["id"]);
            //====================================================================//
            // Try to Load Batch Task
            $this->task = $this->configuration->getTasksRepository()->find((int) $this->status["id"]);
        }
    }

    /**
     * Current Task is Waiting
     */
    public function isWaiting(): bool
    {
        return ($this->status["waiting"] > 0) && empty($this->status["running"]);
    }

    /**
     * Check if Current Task is Running
     */
    public function isRunning(): bool
    {
        return !empty($this->status["running"]);
    }

    /**
     * Check if Current Task is Finished
     */
    public function isFinished(): bool
    {
        return !$this->isWaiting() && !$this->isRunning();
    }

    /**
     * Get the Total Number of Tasks Waiting
     */
    public function getWaiting(): int
    {
        //====================================================================//
        // Task is a Repeatable & Is Booted
        if (!empty($this->task) && is_array($state = $this->task->getJobInputs()["state"] ?? null)) {
            //====================================================================//
            // Get Number of Waiting Steps
            $total = $state[RepeatableJobState::COUNT] ?? 0;
            $progress = $state[RepeatableJobState::EXECUTED] ?? 0;

            return (int) max(0, $total - $progress);
        }

        //====================================================================//
        // Task is Not a Batch
        return ($this->status["waiting"] ?? 0) + ($this->status["running"] ?? 0);
    }

    /**
     * Get the Total Number of Tasks
     */
    public function getTotal(): int
    {
        //====================================================================//
        // Task is a Repeatable & Is Booted
        if (!empty($this->task) && is_array($state = $this->task->getJobInputs()["state"] ?? null)) {
            //====================================================================//
            // Get Total Number of Steps
            $total = $state[RepeatableJobState::COUNT] ?? 0;
            Assert::integer($total);

            return (int) max(0, $total);
        }

        //====================================================================//
        // Task is Not a Batchs
        return ($this->status["total"] ?? 0);
    }

    /**
     * Get Tasks Progress Percentile
     */
    public function getProgress(): int
    {
        $waiting = $this->getWaiting();
        $total = $this->getTotal();

        //====================================================================//
        // Queue is Completed
        if ($total && ($waiting > $total)) {
            return 100;
        }
        //====================================================================//
        // Total is Empty
        if ($total <= 0) {
            return 100;
        }

        //====================================================================//
        // There are Tasks Waiting or Running
        return (int) round(($total - $waiting) / $total * 100);
    }

    /**
     * Get Tasks Label
     */
    public function getLabel(): string
    {
        return $this->jobsManager->getLabel($this->status["settings"]);
    }
}
