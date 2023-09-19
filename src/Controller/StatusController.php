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

namespace Splash\Tasking\Controller;

use Exception;
use Splash\Tasking\Services\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class StatusController extends AbstractController
{
    /**
     * Tasking Status
     *
     * @throws Exception
     *
     * @return JsonResponse
     */
    public function mainAction() : JsonResponse
    {
        //====================================================================//
        // Load Tasks Repository
        $tasks = Configuration::getTasksRepository();
        //====================================================================//
        // Load Worker Repository
        $workers = Configuration::getWorkerRepository();
        //==============================================================================
        // Build Status Array
        $status = array(
            'status' => 'ok',
            'tasks' => $tasks->getTasksSummary(),
            'workers' => $workers->getWorkersStatus(),
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
