<?php

namespace Splash\Tasking\EventListener;

use Exception;
use Monolog\Logger;

use Splash\Tasking\Services\TaskingService;

/**
 * @author Bernard Paquier <eshop.bpaquier@gmail.com>
 */

class TaskingEventListener {

    private $tasking;
    private $logger;


    public function __construct(TaskingService $TaskingService, Logger $logger) {
        $this->tasking      = $TaskingService;
        $this->logger       = $logger;
    }

    public function onSecurityInteractiveLogin() {
        //==============================================================================
        // Check Tasking Processes are started and working        
        $this->tasking->RunTasks();
        return;
    }

}