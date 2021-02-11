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

namespace Splash\Tasking\Events;

use Exception;
use Splash\Tasking\Model\AbstractStaticJob;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Static Tasking Listing Event : Ask for Static Tasks to Declare
 */
class StaticTasksListingEvent extends GenericEvent
{
    /**
     * Declare a new Static Task.
     *
     * @param string $class
     * @param string $token
     * @param int    $frequency
     * @param array  $inputs
     *
     * @throws Exception
     *
     * @return void
     */
    public function addStaticTask(string $class, string $token, int $frequency = 1, array $inputs = array()): void
    {
        //====================================================================//
        // Check if Job already Set in Parameters
        if ($this->hasArgument($class)) {
            return;
        }
        //====================================================================//
        // Safety Check
        if (!class_exists($class)) {
            throw new Exception(sprintf("Job Class %s not found", $class));
        }
        if (!is_subclass_of($class, AbstractStaticJob::class)) {
            throw new Exception(sprintf("Job Class %s must extends %s", $class, AbstractStaticJob::class));
        }
        if (strlen($token) < 5) {
            throw new Exception(sprintf("Job Token for %s task cannot be empty", $class));
        }
        //====================================================================//
        // Add Job Definition
        $this->setArgument($class, array(
            "class" => self::class,             // The Job Class
            "frequency" => $frequency,          // Do Every ? x 60 Minutes
            "token" => $token,                  // Job Static Token
            "inputs" => $inputs,                // Job Specific Inputs
        ));
    }
}
