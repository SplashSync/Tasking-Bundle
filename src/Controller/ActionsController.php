<?php

namespace Splash\Tasking\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

use Splash\Tasking\Entity\Task;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */
class ActionsController extends Controller
{
    /**
     * Start Tasking Supervisor on This Machine
     *
     * @return Response
     */
    public function startAction() : Response
    {
        //==============================================================================
        // Dispatch tasking Bundle Check Event
        $this->get("event_dispatcher")->dispatch("tasking.check");
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
        // Execute Test
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(360);
        $process->setWorkingDirectory($process->getWorkingDirectory()."/..");
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo '! '.nl2br($buffer).PHP_EOL."</br>";
            } elseif ($buffer != ".") {
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
