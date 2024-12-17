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

namespace BadPixxel\Tasking\Actions;

use BadPixxel\Tasking\Services\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Get Server Tasking Status as Json Response
 */
class Status extends AbstractController
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function __invoke() : JsonResponse
    {
        //==============================================================================
        // Build Status Array
        $status = array(
            'status' => 'ok',
            'tasks' => $this->configuration->getTasksRepository()->getTasksSummary(),
            'workers' => $this->configuration->getWorkerRepository()->getWorkersStatus(),
        );
        if ($status["workers"]["total"] != $status["workers"]["disabled"]) {
            //====================================================================//
            // IF No Worker is Running
            if ($status["workers"]["running"] < 1) {
                $status["status"] = "No Worker Running!";
            }
            //====================================================================//
            // IF No Supervisor is Running
            if ($status["workers"]["supervisor"] < 1) {
                $status["status"] = "No Supervisor Running!";
            }
        }

        //==============================================================================
        // Render response
        return new JsonResponse($status);
    }
}
