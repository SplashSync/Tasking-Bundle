<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tasking\Paddock\Tracks;

use BadPixxel\Paddock\Core\Models\Tracks\AbstractTrack;
use Exception;
use Splash\Tasking\Paddock\Collector\WorkersCollector;
use Splash\Tasking\Services\Configuration;

class WorkersCheckerTrack extends AbstractTrack
{
    /**
     * Track Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct("tasking-workers");

        //====================================================================//
        // Track Configuration
        //====================================================================//
        $this->enabled = true;
        $this->description = "[TASKING] Check Workers Status";
        $this->collector = WorkersCollector::getCode();

        //====================================================================//
        // Load Tasking Configuration
        //====================================================================//

        $maxWorkers = Configuration::getSupervisorMaxWorkers();

        //====================================================================//
        // Add Rules
        //====================================================================//

        //====================================================================//
        // Check Workers are Running
        $this->addRule("running", array(
            // At Least One Worker is Running
            "ne" => true,
            // All Expected Workers are Running (-1 if restarting)
            "gte" => array("warning" => $maxWorkers - 1),
            // Register Value for Metrics
            "metric" => "Running"
        ));
        //====================================================================//
        // Check Supervisors are Running
        $this->addRule("supervisor", array(
            // At Least One Supervisor
            "ne" => array("warning" => $maxWorkers),
            // Register Value for Metrics
            "metric" => "Supervisor"
        ));
        //====================================================================//
        // Check Expected Total Number of Worker
        $this->addRule("total", array(
            // At Least One Worker
            "ne" => true,
            // All Expected Workers
            "gte" => array("warning" => $maxWorkers),
            // Register Value for Metrics
            "metric" => "Total"
        ));
        //====================================================================//
        // Check Number of Sleeping Worker
        $this->addRule("sleeping", array(
            // Register Value for Metrics
            "metric" => "Sleeping"
        ));
    }
}
