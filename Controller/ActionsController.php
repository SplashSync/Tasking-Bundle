<?php

namespace Splash\Tasking\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
    public function startAction()
    {
        //==============================================================================
        // Load Tasking Service & Check Supervisor
        $Running    = $this->get("TaskingService")->SupervisorCheckIsRunning();
        //==============================================================================
        // Render response
        return new Response(
                "Tasking : Start Supervisor => " . ($Running ? "OK" : "KO" ),  
                Response::HTTP_OK,
                array('content-type' => 'text/html')
                );
    } 
}
