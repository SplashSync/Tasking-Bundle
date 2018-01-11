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
     * @abstract    Start Tasking Supervisor on This Machine
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function startAction( Request $request)
    {
        //====================================================================//
        // Check Crontab is Setuped     
        if ( $this->get("TaskingService")->CrontabCheck() == Task::CRONTAB_DISABLED) {
            //====================================================================//
            // Ensure Supervisor is Running
            $Result = $this->get("TaskingService")->SupervisorCheckIsRunning();
        } else {
            $Result = True;
        }
        
        //==============================================================================
        // Init Static Tasks 
        $this->get("TaskingService")->StaticTasksInit();
        
        //==============================================================================
        // Render response
        return new Response(
                ($Result ? "Ok" : "KO"),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
                );
    } 
    
    
    /**
     * @abstract    Start Tasking Bundle Tests
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testAction()
    {
        //====================================================================//
        // Stop Output Buffering
        ob_end_flush();

        //====================================================================//
        // Prepare for Running Test
        $Command = "phpunit ";

        //====================================================================//
        // Execute Test
        $process = new Process($Command);
        $process->setTimeout(360);
        $process->setWorkingDirectory($process->getWorkingDirectory() . "/..");
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo '! '.nl2br($buffer) . PHP_EOL . "</br>";
            } elseif ($buffer != ".") {
                echo '> '.nl2br($buffer) . PHP_EOL . "</br>";
            } else {
                echo nl2br($buffer);
            }
            flush();
        });

        //====================================================================//
        // Display result message
        if ( !$process->isSuccessful() ) {
            echo "</br></br>!!!!!! FAIL!";
        } else {
            echo "</br></br>>>>>>> PASS!";
        }            
        ob_start();
                
        exit;
    } 
    
}
