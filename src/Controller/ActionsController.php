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

use ReflectionException;
use Splash\Tasking\Services\TasksManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class ActionsController extends AbstractController
{
    /**
     * Start Tasking Supervisor on This Machine
     *
     * @throws ReflectionException
     *
     * @return Response
     */
    public function startAction() : Response
    {
        //==============================================================================
        // Dispatch tasking Bundle Check Event
        TasksManager::check();

        //==============================================================================
        // Render response
        return new Response("Ok", Response::HTTP_OK, array('content-type' => 'text/html'));
    }

    /**
     * Start Tasking Bundle Tests
     *
     * @return Response
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function testAction(): Response
    {
        //====================================================================//
        // Stop Output Buffering
        ob_end_flush();
        //====================================================================//
        // Prepare for Running Test
        $command = "phpunit ";
        //====================================================================//
        // Execute Test (SF 4 Versions)
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(360);
        $process->setWorkingDirectory($process->getWorkingDirectory()."/..");
        $process->run(function ($type, $buffer): void {
            if (Process::ERR === $type) {
                echo '! '.nl2br($buffer).PHP_EOL."</br>";
            } elseif ("." != $buffer) {
                echo '> '.nl2br($buffer).PHP_EOL."</br>";
            } else {
                echo nl2br($buffer);
            }
            flush();
        });
        //====================================================================//
        // Display result message
        $response = $process->isSuccessful()
                ? "</br></br>>>>>>> PASS!"
                : "</br></br>!!!!!! FAIL!";
        ob_start();

        return new Response($response, Response::HTTP_OK);
    }
}
