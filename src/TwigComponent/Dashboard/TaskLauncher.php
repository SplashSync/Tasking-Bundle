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

namespace BadPixxel\Tasking\TwigComponent\Dashboard;

use BadPixxel\Tasking\Services\Jobs\JobsManager;
use BadPixxel\Tasking\Services\TasksManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent(
    name: "Tasking:Test:Launcher",
    template: "@BadpixxelTasking/Component/Dashboard/launcher.html.twig"
)]
class TaskLauncher extends AbstractController
{
    use DefaultActionTrait;

    /**
     * List of Job IDs
     *
     * @var array<string, array>
     */
    #[LiveProp]
    public array $jobs = array();

    public function __construct(
        private readonly JobsManager $jobsManager,
        private readonly TasksManager $tasksManager,
    ) {
    }

    /**
     * Fetch List of Configured Jobs
     */
    #[PostMount]
    public function fetchJobsIds(): void
    {
        $this->jobs = $this->jobsManager->getAllConfiguredJobs();
    }

    /**
     * Fetch List of Configured Jobs
     */
    #[LiveAction]
    public function start(#[LiveArg] string $jobId): void
    {
        $this->tasksManager->start($jobId, array());
    }

    /**
     * @return JobsManager
     */
    public function getJobsManager(): JobsManager
    {
        return $this->jobsManager;
    }
}
