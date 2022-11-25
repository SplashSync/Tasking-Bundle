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
use Splash\Tasking\Paddock\Collector\TasksCollector;
use Splash\Tasking\Paddock\Collector\WorkersCollector;

class TaskingCheckerTrack extends AbstractTrack
{
    /**
     * Track Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct("tasking-tasks");

        //====================================================================//
        // Track Configuration
        //====================================================================//
        $this->enabled = true;
        $this->description = "[TASKING] Check Tasks Status";
        $this->collector = TasksCollector::getCode();

        //====================================================================//
        // Add Rules
        //====================================================================//

        //====================================================================//
        // Check Workers are Running
        $this->addRule("running", array("collector" => WorkersCollector::getCode(), "ne" => true));

        //====================================================================//
        // Check Number of Tasks Waiting
        $this->addRule("waiting", array("lte" => array("error" => 1000, "warning" => 500), "metric" => "Waiting"));
        //====================================================================//
        // Check Number of Tasks Finished
        $this->addRule("finished", array("metric" => "Finished"));
        //====================================================================//
        // Check Number of Tasks Total
        $this->addRule("total", array("metric" => "Total"));
        //====================================================================//
        // Check Number of Active Token
        $this->addRule("token", array("metric" => "Tokens"));
    }
}
