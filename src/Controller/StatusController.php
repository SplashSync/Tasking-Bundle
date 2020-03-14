<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Controller;

use Splash\Tasking\Entity\Task;
use Splash\Tasking\Entity\Worker;
use Splash\Tasking\Repository\TaskRepository;
use Splash\Tasking\Repository\WorkerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class StatusController extends Controller
{
    /**
     * Tasking Status
     *
     * @return JsonResponse
     */
    public function mainAction() : JsonResponse
    {
        $manager = $this->get("doctrine")->getManager();
        //====================================================================//
        // Load Tasks Repository
        /** @var TaskRepository $tasks */
        $tasks = $manager->getRepository(Task::class);
        //====================================================================//
        // Load Worker Repository
        /** @var WorkerRepository $tasks */
        $workers = $manager->getRepository(Worker::class);
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
