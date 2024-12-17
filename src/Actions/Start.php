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

use BadPixxel\Tasking\Services\TasksManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Local Supervisor is Running
 */
class Start extends AbstractController
{
    /**
     * Ensure Tasking Supervisor on This Machine
     */
    public function startAction(TasksManager $tasksManager) : Response
    {
        //==============================================================================
        // Dispatch tasking Bundle Check Event
        $tasksManager->checkSupervisor();

        //==============================================================================
        // Render response
        return new Response("Ok", Response::HTTP_OK, array('content-type' => 'text/html'));
    }
}
