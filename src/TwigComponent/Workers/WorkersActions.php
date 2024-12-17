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

namespace BadPixxel\Tasking\TwigComponent\Workers;

use BadPixxel\Tasking\Services\WorkersManager;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Render Status of Tasking Workers
 */
#[AsLiveComponent(
    name:       "Tasking:Workers:Actions",
    template:   "@BadpixxelTasking/Component/Workers/actions.html.twig"
)]
class WorkersActions
{
    use DefaultActionTrait;

    public function __construct(
        private readonly WorkersManager $workersManager
    ) {
    }

    /**
     * Check Supervisor is Running
     */
    #[LiveAction]
    public function check(): void
    {
        //====================================================================//
        // Check Supervisors & Crontab
        $this->workersManager->checkSupervisor();
    }

    /**
     * Start All Workers
     */
    #[LiveAction]
    public function start(): void
    {
        //====================================================================//
        // Request All Active Workers to Start
        $this->workersManager->setupAllWorkers(true);
        //====================================================================//
        // Check Supervisors & Crontab
        $this->workersManager->checkSupervisor();
    }

    /**
     * Stop All Workers
     */
    #[LiveAction]
    public function stop(): void
    {
        //====================================================================//
        // Request All Active Workers to Start
        $this->workersManager->setupAllWorkers(false);
    }
}
